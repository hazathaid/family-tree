<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Exports a family as a GEDCOM 5.5.1 LINEAGE-LINKED document.
 *
 * Only the five stored base relationships (father, mother, child, husband,
 * wife) are mapped into GEDCOM INDI/FAM records. No derived kinship is ever
 * persisted or computed here.
 */
class GedcomExportService
{
    public const VERSION = '5.5.1';

    private const MAX_LINE_LENGTH = 190;

    public function __construct(
        private readonly FamilyMemberRepositoryInterface $members,
        private readonly RelationshipRepositoryInterface $relationships,
    ) {}

    public function filename(Family $family): string
    {
        return Str::slug($family->name).'-family-tree.ged';
    }

    public function export(Family $family): string
    {
        $members = $this->members->allForFamily($family);
        $families = $this->buildFamilies($members, $this->relationships->allForFamily($family));

        $lines = [
            '0 HEAD',
            '1 SOUR Family Tree Platform Indonesia',
            '1 GEDC',
            '2 VERS '.self::VERSION,
            '2 FORM LINEAGE-LINKED',
            '1 CHAR UTF-8',
            '1 SUBM @SUBM@',
            '0 @SUBM@ SUBM',
        ];
        foreach ($this->noteLines('1 NAME', $family->name) as $line) {
            $lines[] = $line;
        }

        foreach ($members as $member) {
            $lines[] = '0 @I'.$member->id.'@ INDI';
            $lines[] = '1 NAME '.$this->value($member->full_name);
            if ($member->gender !== null) {
                $lines[] = '1 SEX '.($member->gender === 'female' ? 'F' : 'M');
            }
            if ($member->birth_date !== null) {
                $lines[] = '1 BIRT';
                $lines[] = '2 DATE '.strtoupper($member->birth_date->format('j M Y'));
                if ($member->birth_place !== null && $member->birth_place !== '') {
                    $lines[] = '2 PLAC '.$this->value($member->birth_place);
                }
            }
            if (! $member->is_alive || $member->death_date !== null) {
                $lines[] = '1 DEAT';
                if ($member->death_date !== null) {
                    $lines[] = '2 DATE '.strtoupper($member->death_date->format('j M Y'));
                }
                if ($member->death_place !== null && $member->death_place !== '') {
                    $lines[] = '2 PLAC '.$this->value($member->death_place);
                }
            }
            if ($member->biography !== null && $member->biography !== '') {
                foreach ($this->noteLines('1 NOTE', $member->biography) as $line) {
                    $lines[] = $line;
                }
            }
            foreach ($families['child_families'][$member->id] ?? [] as $xref) {
                $lines[] = '1 FAMC @'.$xref.'@';
            }
            foreach ($families['spouse_families'][$member->id] ?? [] as $xref) {
                $lines[] = '1 FAMS @'.$xref.'@';
            }
        }

        foreach ($families['records'] as $familyRecord) {
            $lines[] = '0 @'.$familyRecord['xref'].'@ FAM';
            foreach ($familyRecord['husbands'] as $id) {
                $lines[] = '1 HUSB @I'.$id.'@';
            }
            foreach ($familyRecord['wives'] as $id) {
                $lines[] = '1 WIFE @I'.$id.'@';
            }
            foreach ($familyRecord['children'] as $id) {
                $lines[] = '1 CHIL @I'.$id.'@';
            }
        }

        $lines[] = '0 TRLR';

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * Maps base relationships into GEDCOM families.
     *
     * @param  Collection<int, FamilyMember>  $members
     * @param  Collection<int, MemberRelationship>  $edges
     * @return array{records: list<array{xref: string, husbands: list<int>, wives: list<int>, children: list<int>}>, child_families: array<int, list<string>>, spouse_families: array<int, list<string>>}
     */
    private function buildFamilies(Collection $members, Collection $edges): array
    {
        $memberById = $members->keyBy('id');
        $parentsOf = [];
        $husbandsOf = [];
        $wivesOf = [];

        foreach ($edges as $edge) {
            $source = $edge->source_member_id;
            $target = $edge->target_member_id;

            switch ($edge->relationship_type) {
                case MemberRelationship::TYPE_FATHER:
                case MemberRelationship::TYPE_MOTHER:
                    $parentsOf[$target][$source] = true;
                    break;
                case MemberRelationship::TYPE_CHILD:
                    $parentsOf[$source][$target] = true;
                    break;
                case MemberRelationship::TYPE_HUSBAND:
                    $husbandsOf[$target][$source] = true;
                    $wivesOf[$source][$target] = true;
                    break;
                case MemberRelationship::TYPE_WIFE:
                    $wivesOf[$target][$source] = true;
                    $husbandsOf[$source][$target] = true;
                    break;
            }
        }

        $families = [];

        foreach ($parentsOf as $childId => $parentIds) {
            $ids = array_keys($parentIds);
            sort($ids);
            $key = implode(',', $ids);
            $families[$key]['parents'] = $ids;
            $families[$key]['children'][$childId] = true;
        }

        foreach ($husbandsOf as $wifeId => $husbandIds) {
            foreach (array_keys($husbandIds) as $husbandId) {
                $ids = [$husbandId, $wifeId];
                sort($ids);
                $key = implode(',', $ids);
                if (isset($families[$key])) {
                    continue;
                }
                $families[$key]['parents'] = $ids;
                $families[$key]['children'] = [];
            }
        }

        $records = [];
        $childFamilies = [];
        $spouseFamilies = [];
        $familyIndex = 0;

        foreach ($families as $family) {
            $familyIndex++;
            $xref = 'F'.$familyIndex;
            $husbands = [];
            $wives = [];
            foreach ($family['parents'] as $parentId) {
                if (($memberById->get($parentId)?->gender) === 'female') {
                    $wives[] = $parentId;
                } else {
                    $husbands[] = $parentId;
                }
            }
            sort($husbands);
            sort($wives);
            $childIds = array_keys($family['children']);
            sort($childIds);

            $records[] = [
                'xref' => $xref,
                'husbands' => $husbands,
                'wives' => $wives,
                'children' => $childIds,
            ];

            foreach ($husbands as $id) {
                $spouseFamilies[$id][$xref] = true;
            }
            foreach ($wives as $id) {
                $spouseFamilies[$id][$xref] = true;
            }
            foreach ($childIds as $id) {
                $childFamilies[$id][$xref] = true;
            }
        }

        return [
            'records' => $records,
            'child_families' => array_map('array_keys', $childFamilies),
            'spouse_families' => array_map('array_keys', $spouseFamilies),
        ];
    }

    /**
     * @return list<string>
     */
    private function noteLines(string $prefix, string $value): array
    {
        $lines = [];
        $segments = preg_split('/\r\n|\r|\n/', $value) ?: [];

        foreach ($segments as $index => $segment) {
            $tag = $index === 0 ? $prefix : '1 CONT';
            foreach ($this->wrap($segment) as $offset => $part) {
                $lines[] = ($offset === 0 ? $tag : '1 CONC').' '.$this->value($part);
            }
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function wrap(string $value): array
    {
        $remaining = trim($value);
        if ($remaining === '') {
            return [];
        }

        $parts = [];
        while (mb_strlen($remaining) > self::MAX_LINE_LENGTH) {
            $segment = mb_substr($remaining, 0, self::MAX_LINE_LENGTH);
            $cut = mb_strrpos($segment, ' ');
            $cut = $cut === false ? self::MAX_LINE_LENGTH : $cut;
            $parts[] = mb_substr($remaining, 0, $cut);
            $remaining = ltrim(mb_substr($remaining, $cut));
        }
        if ($remaining !== '') {
            $parts[] = $remaining;
        }

        return $parts;
    }

    private function value(string $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n"], ' ', trim($value));

        return str_replace('@', '@@', $value);
    }
}
