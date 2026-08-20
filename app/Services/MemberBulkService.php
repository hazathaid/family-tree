<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\FamilyMember;
use App\Models\User;
use App\Repositories\Contracts\FamilyBranchRepositoryInterface;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MemberBulkService
{
    public function __construct(
        private readonly FamilyMemberRepositoryInterface $members,
        private readonly FamilyBranchRepositoryInterface $branches,
        private readonly RelationshipCacheService $relationshipCache,
        private readonly TreeCacheService $treeCache,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @return array{imported: int, skipped: int, errors: array<int, array{row: int, full_name: string|null, errors: array<string, array<int, string>>}>}
     */
    public function import(User $user, Family $family, string $csv): array
    {
        $rows = $this->parseRows($csv);

        if ($rows === []) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => []];
        }

        $summary = ['imported' => 0, 'skipped' => 0, 'errors' => []];
        $branchByUuid = $this->branchIndex($family);

        DB::transaction(function () use ($user, $family, $rows, $branchByUuid, &$summary): void {
            foreach ($rows as $index => $row) {
                $result = $this->rowAttributes($row, $family, $branchByUuid, $index + 2);

                if ($result['ok']) {
                    $this->members->create([...$result['attributes'], 'family_id' => $family->id, 'created_by' => $user->id]);
                    $summary['imported']++;
                } else {
                    $summary['errors'][] = $result['error'];
                    $summary['skipped']++;
                }
            }

            $this->relationshipCache->invalidateFamily($family->id);
            $this->treeCache->invalidateFamily($family->id);
            $this->activityLog->record($family->id, $user, ActivityLog::MEMBERS_IMPORTED, [
                'imported' => $summary['imported'],
                'skipped' => $summary['skipped'],
            ]);
        });

        return $summary;
    }

    public function export(Family $family): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            return '';
        }

        fputcsv($stream, [
            'uuid',
            'full_name',
            'nickname',
            'gender',
            'religion',
            'birth_date',
            'birth_place',
            'is_alive',
            'death_date',
            'death_place',
            'biography',
            'branch_uuid',
            'branch_name',
        ]);

        foreach ($this->members->cursorForFamily($family) as $member) {
            fputcsv($stream, [
                $member->uuid,
                $member->full_name,
                $member->nickname,
                $member->gender,
                $member->religion,
                $member->birth_date?->format('Y-m-d'),
                $member->birth_place,
                $member->is_alive ? '1' : '0',
                $member->death_date?->format('Y-m-d'),
                $member->death_place,
                $member->biography,
                $member->branch?->uuid,
                $member->branch?->name,
            ]);
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content === false ? '' : $content;
    }

    /**
     * @return list<array<string, string>>
     */
    private function parseRows(string $csv): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $csv) ?: [];
        $lines = array_values(array_filter($lines, fn (string $line): bool => trim($line) !== ''));

        if ($lines === []) {
            return [];
        }

        $delimiter = $this->delimiter($lines[0]);
        $headers = str_getcsv($lines[0], $delimiter);
        $headers = array_map(fn (string $header): string => strtolower(trim($header)), $headers);

        if (! in_array('full_name', $headers, true)) {
            throw ValidationException::withMessages([
                'file' => ['The CSV file must contain a "full_name" column.'],
            ]);
        }

        $rows = [];

        foreach (array_slice($lines, 1) as $line) {
            $values = str_getcsv($line, $delimiter);
            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = trim($values[$index] ?? '');
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function delimiter(string $line): string
    {
        $semicolons = substr_count($line, ';');
        $commas = substr_count($line, ',');

        return $semicolons > $commas ? ';' : ',';
    }

    /**
     * @param  array<string, FamilyBranch>  $branchByUuid
     * @return array{ok: true, attributes: array<string, mixed>}|array{ok: false, error: array{row: int, full_name: string|null, errors: array<string, array<int, string>>}}
     */
    private function rowAttributes(array $row, Family $family, array $branchByUuid, int $rowNumber): array
    {
        $row['is_alive'] = $this->normalizeBoolean($row['is_alive'] ?? '');

        $validator = Validator::make($row, [
            'full_name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'religion' => ['nullable', Rule::in(FamilyMember::RELIGIONS)],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'is_alive' => ['nullable', 'boolean'],
            'death_date' => ['nullable', 'date', 'after_or_equal:birth_date'],
            'death_place' => ['nullable', 'string', 'max:255'],
            'biography' => ['nullable', 'string'],
            'branch_uuid' => ['nullable', 'uuid'],
        ]);

        if ($validator->fails()) {
            return [
                'ok' => false,
                'error' => [
                    'row' => $rowNumber,
                    'full_name' => $row['full_name'] !== '' ? $row['full_name'] : null,
                    'errors' => $validator->errors()->toArray(),
                ],
            ];
        }

        $validated = $validator->validated();
        $branchId = null;
        $branchUuid = ($validated['branch_uuid'] ?? '') !== '' ? $validated['branch_uuid'] : null;

        if ($branchUuid !== null) {
            $branch = $branchByUuid[$branchUuid] ?? null;

            if ($branch === null) {
                return [
                    'ok' => false,
                    'error' => [
                        'row' => $rowNumber,
                        'full_name' => $validated['full_name'],
                        'errors' => ['branch_uuid' => ['The selected branch does not belong to this family.']],
                    ],
                ];
            }

            $branchId = $branch->id;
        }

        $isAlive = $this->boolean($validated['is_alive'] ?? null, true);
        $deathDate = $this->emptyToNull($validated['death_date'] ?? null);

        if ($deathDate !== null) {
            $isAlive = false;
        }

        return [
            'ok' => true,
            'attributes' => [
                'family_branch_id' => $branchId,
                'full_name' => $validated['full_name'],
                'nickname' => $this->emptyToNull($validated['nickname'] ?? null),
                'gender' => $this->emptyToNull($validated['gender'] ?? null),
                'religion' => $this->emptyToNull($validated['religion'] ?? null),
                'birth_date' => $this->emptyToNull($validated['birth_date'] ?? null),
                'birth_place' => $this->emptyToNull($validated['birth_place'] ?? null),
                'is_alive' => $isAlive,
                'death_date' => $deathDate,
                'death_place' => $this->emptyToNull($validated['death_place'] ?? null),
                'biography' => $this->emptyToNull($validated['biography'] ?? null),
            ],
        ];
    }

    /**
     * @return array<string, FamilyBranch>
     */
    private function branchIndex(Family $family): array
    {
        return $this->branches->allForFamily($family)
            ->keyBy('uuid')
            ->all();
    }

    private function boolean(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function normalizeBoolean(string $value): string
    {
        $lower = strtolower($value);

        if ($lower !== '' && in_array($lower, ['true', 'false', 'yes', 'no', 'y', 'n', '1', '0'], true)) {
            return filter_var($lower, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        return $value;
    }

    private function emptyToNull(mixed $value): mixed
    {
        return $value === '' || $value === null ? null : $value;
    }
}
