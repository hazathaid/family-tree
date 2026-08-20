<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Imports a GEDCOM 5.5.1 LINEAGE-LINKED document into a family.
 *
 * Only the five stored base relationships (father, mother, child, husband,
 * wife) are written. Derived kinship is never computed or stored. The import
 * runs in a transaction and reports a per-record summary.
 */
class GedcomImportService
{
    public function __construct(
        private readonly FamilyMemberRepositoryInterface $members,
        private readonly RelationshipRepositoryInterface $relationships,
        private readonly RelationshipCacheService $relationshipCache,
        private readonly TreeCacheService $treeCache,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @return array{members_created: int, relationships_created: int, members_skipped: int, relationships_skipped: int, errors: array<int, string>}
     */
    public function import(User $user, Family $family, string $content): array
    {
        if (trim($content) === '' || ! str_starts_with(trim($content), '0 HEAD')) {
            throw ValidationException::withMessages([
                'file' => ['The uploaded file is not a valid GEDCOM document.'],
            ]);
        }

        $records = $this->parse($content);
        $individuals = $records['INDI'];
        $families = $records['FAM'];

        $summary = [
            'members_created' => 0,
            'relationships_created' => 0,
            'members_skipped' => 0,
            'relationships_skipped' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($user, $family, $individuals, $families, &$summary): void {
            $memberByXref = [];

            foreach ($individuals as $record) {
                $name = $this->name($record);

                if ($name === null) {
                    $summary['members_skipped']++;

                    continue;
                }

                $birthDate = $this->date($this->nestedValue($record['lines'], 'BIRT', 'DATE'));
                $deathDate = $this->date($this->nestedValue($record['lines'], 'DEAT', 'DATE'));
                $member = $this->members->create([
                    'family_id' => $family->id,
                    'full_name' => $name,
                    'gender' => $this->sex($this->firstValue($record['lines'], 'SEX')),
                    'religion' => null,
                    'birth_date' => $birthDate,
                    'birth_place' => $this->nestedValue($record['lines'], 'BIRT', 'PLAC'),
                    'is_alive' => $deathDate === null,
                    'death_date' => $deathDate,
                    'death_place' => $this->nestedValue($record['lines'], 'DEAT', 'PLAC'),
                    'biography' => $this->firstValue($record['lines'], 'NOTE'),
                    'created_by' => $user->id,
                ]);

                $memberByXref[$record['xref']] = $member;
                $summary['members_created']++;
            }

            foreach ($families as $record) {
                $husband = $this->xref($this->firstValue($record['lines'], 'HUSB'));
                $wife = $this->xref($this->firstValue($record['lines'], 'WIFE'));
                $husbandMember = $husband !== null ? ($memberByXref[$husband] ?? null) : null;
                $wifeMember = $wife !== null ? ($memberByXref[$wife] ?? null) : null;

                foreach ($this->allValues($record['lines'], 'CHIL') as $childXref) {
                    $child = $memberByXref[$this->xref($childXref)] ?? null;

                    if ($child === null) {
                        continue;
                    }

                    if ($husbandMember !== null) {
                        $this->createParentEdge($family, $husbandMember, $child, MemberRelationship::TYPE_FATHER, $summary);
                    }

                    if ($wifeMember !== null) {
                        $this->createParentEdge($family, $wifeMember, $child, MemberRelationship::TYPE_MOTHER, $summary);
                    }
                }

                if ($husbandMember !== null && $wifeMember !== null) {
                    $this->createSpouseEdges($family, $husbandMember, $wifeMember, $summary);
                }
            }

            $this->relationshipCache->invalidateFamily($family->id);
            $this->treeCache->invalidateFamily($family->id);

            $this->activityLog->record($family->id, $user, ActivityLog::GEDCOM_IMPORTED, [
                'members_created' => $summary['members_created'],
                'relationships_created' => $summary['relationships_created'],
            ]);
        });

        return $summary;
    }

    /**
     * @param  array{members_created: int, relationships_created: int, members_skipped: int, relationships_skipped: int, errors: array<int, string>}  $summary
     */
    private function createParentEdge(Family $family, FamilyMember $parent, FamilyMember $child, string $type, array &$summary): void
    {
        if ($this->relationships->existsEdge($family, $parent->id, $child->id, $type)) {
            $summary['relationships_skipped']++;

            return;
        }

        $existingParent = $this->relationships->biologicalParentForChild($family, $child, $type);

        if ($existingParent instanceof MemberRelationship && $existingParent->source_member_id !== $parent->id) {
            $summary['relationships_skipped']++;

            return;
        }

        $this->relationships->create([
            'family_id' => $family->id,
            'source_member_id' => $parent->id,
            'target_member_id' => $child->id,
            'relationship_type' => $type,
        ]);
        $summary['relationships_created']++;
    }

    /**
     * @param  array{members_created: int, relationships_created: int, members_skipped: int, relationships_skipped: int, errors: array<int, string>}  $summary
     */
    private function createSpouseEdges(Family $family, FamilyMember $husband, FamilyMember $wife, array &$summary): void
    {
        $types = [
            [MemberRelationship::TYPE_HUSBAND, $husband, $wife],
            [MemberRelationship::TYPE_WIFE, $wife, $husband],
        ];

        foreach ($types as [$type, $source, $target]) {
            if ($this->relationships->existsEdge($family, $source->id, $target->id, $type)) {
                $summary['relationships_skipped']++;

                continue;
            }

            $this->relationships->create([
                'family_id' => $family->id,
                'source_member_id' => $source->id,
                'target_member_id' => $target->id,
                'relationship_type' => $type,
            ]);
            $summary['relationships_created']++;
        }
    }

    /**
     * @return array{INDI: list<array{xref: string, lines: list<array{level: int, tag: string, value: ?string}>}>, FAM: list<array{xref: string, lines: list<array{level: int, tag: string, value: ?string}>}>}
     */
    private function parse(string $content): array
    {
        $individuals = [];
        $families = [];
        $currentIndex = null;
        $currentList = null;

        foreach (preg_split('/\r\n|\r|\n/', $content) ?: [] as $line) {
            $line = rtrim($line);

            if ($line === '' || ! preg_match('/^(\d+) ?(.*)$/', $line, $match)) {
                continue;
            }

            $level = (int) $match[1];
            $payload = $match[2];

            if ($level === 0) {
                $record = $this->topLevelRecord($payload);
                $currentIndex = null;
                $currentList = null;

                if ($record['tag'] === 'INDI') {
                    $individuals[] = ['xref' => $record['xref'], 'lines' => []];
                    $currentIndex = count($individuals) - 1;
                    $currentList = 'INDI';
                } elseif ($record['tag'] === 'FAM') {
                    $families[] = ['xref' => $record['xref'], 'lines' => []];
                    $currentIndex = count($families) - 1;
                    $currentList = 'FAM';
                }

                continue;
            }

            if ($currentIndex === null) {
                continue;
            }

            $line = [
                'level' => $level,
                'tag' => $this->tag($payload),
                'value' => $this->value($payload),
            ];

            if ($currentList === 'INDI') {
                $individuals[$currentIndex]['lines'][] = $line;
            } else {
                $families[$currentIndex]['lines'][] = $line;
            }
        }

        return ['INDI' => $individuals, 'FAM' => $families];
    }

    /**
     * @return array{xref: string, tag: string}
     */
    private function topLevelRecord(string $payload): array
    {
        if (preg_match('/^@(.+)@ (.*)$/', $payload, $match)) {
            return ['xref' => $match[1], 'tag' => $match[2]];
        }

        return ['xref' => '', 'tag' => $payload];
    }

    private function tag(string $payload): string
    {
        return preg_match('/^([A-Z0-9_]+)/', $payload, $match) ? $match[1] : $payload;
    }

    private function value(string $payload): ?string
    {
        if (! preg_match('/^[A-Z0-9_]+ ?(.*)$/', $payload, $match)) {
            return null;
        }

        return $match[1] !== '' ? $match[1] : null;
    }

    /**
     * @param  list<array{level: int, tag: string, value: ?string}>  $lines
     */
    private function firstValue(array $lines, string $tag): ?string
    {
        foreach ($lines as $line) {
            if ($line['tag'] === $tag && $line['value'] !== null) {
                return $line['value'];
            }
        }

        return null;
    }

    /**
     * @param  list<array{level: int, tag: string, value: ?string}>  $lines
     * @return list<?string>
     */
    private function allValues(array $lines, string $tag): array
    {
        $values = [];

        foreach ($lines as $line) {
            if ($line['tag'] === $tag) {
                $values[] = $line['value'];
            }
        }

        return $values;
    }

    /**
     * @param  list<array{level: int, tag: string, value: ?string}>  $lines
     */
    private function nestedValue(array $lines, string $parentTag, string $childTag): ?string
    {
        foreach ($lines as $index => $line) {
            if ($line['tag'] !== $parentTag) {
                continue;
            }

            for ($cursor = $index + 1; $cursor < count($lines); $cursor++) {
                if ($lines[$cursor]['level'] <= $line['level']) {
                    break;
                }

                if ($lines[$cursor]['tag'] === $childTag) {
                    return $lines[$cursor]['value'];
                }
            }
        }

        return null;
    }

    private function xref(?string $value): ?string
    {
        if ($value === null || ! preg_match('/^@(.+)@$/', $value, $match)) {
            return null;
        }

        return $match[1];
    }

    private function name(array $record): ?string
    {
        $raw = $this->firstValue($record['lines'], 'NAME');

        if ($raw === null) {
            return null;
        }

        $name = preg_replace('/\s*\/([^\/]*)\/\s*/', ' $1 ', $raw);
        $name = preg_replace('/\s+/', ' ', trim($name ?? ''));

        return $name === '' ? null : $name;
    }

    private function sex(?string $value): ?string
    {
        return match (strtoupper((string) $value)) {
            'F', 'FEMALE' => 'female',
            'M', 'MALE' => 'male',
            default => null,
        };
    }

    private function date(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = trim(preg_replace('/^(ABT|ABOUT|CAL|EST|BEF|AFT|INT|FROM|TO)\s+/i', '', $value) ?? $value);

        foreach (['j M Y', 'M Y', 'Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $clean);
            } catch (Throwable) {
                $date = false;
            }

            if ($date === false) {
                continue;
            }

            if ($format === 'M Y') {
                $date = $date->startOfMonth();
            } elseif ($format === 'Y') {
                $date = $date->startOfYear();
            }

            return $date->format('Y-m-d');
        }

        return null;
    }
}
