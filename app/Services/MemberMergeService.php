<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberAccountInvitation;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberMergeService
{
    private const FILLABLE_FIELDS = [
        'nickname',
        'gender',
        'religion',
        'birth_date',
        'birth_place',
        'biography',
        'profile_photo',
        'profile_photo_thumbnail',
        'family_branch_id',
    ];

    private const NAME_STOPWORDS = ['alm', 'almh', 'h', 'hj', 'dr', 'drs', 'ir', 'prof', 'ny', 'nn', 'bpk', 'ibu'];

    public function __construct(
        private readonly FamilyMemberRepositoryInterface $members,
        private readonly RelationshipCacheService $relationshipCache,
        private readonly TreeCacheService $treeCache,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * Detect candidate duplicate pairs within a family using normalized names.
     *
     * @return array<int, array{primary: FamilyMember, duplicate: FamilyMember, confidence: string, reasons: array<int, string>}>
     */
    public function detectDuplicates(Family $family, int $limit = 25): array
    {
        $limit = max(1, min($limit, 100));
        $groups = [];

        foreach ($this->members->cursorForFamily($family) as $member) {
            $key = $this->normalizeName($member->full_name);

            if ($key === null) {
                continue;
            }

            $groups[$key][] = $member;
        }

        $pairs = [];

        foreach ($groups as $members) {
            if (count($members) < 2) {
                continue;
            }

            $primary = array_shift($members);

            foreach ($members as $duplicate) {
                $pairs[] = [
                    'primary' => $primary->loadMissing(['family', 'branch']),
                    'duplicate' => $duplicate->loadMissing(['family', 'branch']),
                    'confidence' => $this->confidence($primary, $duplicate),
                    'reasons' => $this->reasons($primary, $duplicate),
                ];

                if (count($pairs) >= $limit) {
                    return $pairs;
                }
            }
        }

        return $pairs;
    }

    public function merge(User $user, FamilyMember $primary, FamilyMember $duplicate): FamilyMember
    {
        if ($primary->id === $duplicate->id) {
            throw ValidationException::withMessages([
                'duplicate_uuid' => ['A member cannot be merged with themselves.'],
            ]);
        }

        if ($primary->family_id !== $duplicate->family_id) {
            throw ValidationException::withMessages([
                'duplicate_uuid' => ['Both members must belong to the same family.'],
            ]);
        }

        if ($primary->user_id !== null && $duplicate->user_id !== null && $primary->user_id !== $duplicate->user_id) {
            throw ValidationException::withMessages([
                'duplicate_uuid' => ['Both members are linked to different user accounts and cannot be merged automatically.'],
            ]);
        }

        return DB::transaction(function () use ($user, $primary, $duplicate): FamilyMember {
            $this->transferAccount($primary, $duplicate);
            $this->fillBlanks($primary, $duplicate);
            $this->reassignRelationships($primary, $duplicate);
            $this->reassignPhotoTags($primary, $duplicate);
            $this->reassignInvitations($primary, $duplicate);
            $duplicate->delete();

            $this->relationshipCache->invalidateFamily($primary->family_id);
            $this->treeCache->invalidateFamily($primary->family_id);
            $this->activityLog->memberMerged($user, $primary->refresh(), $duplicate);

            return $primary->refresh()->load(['family', 'branch']);
        });
    }

    private function transferAccount(FamilyMember $primary, FamilyMember $duplicate): void
    {
        if ($primary->user_id !== null || $duplicate->user_id === null) {
            return;
        }

        $userId = $duplicate->user_id;
        $duplicate->forceFill(['user_id' => null])->save();
        $primary->forceFill(['user_id' => $userId])->save();
    }

    private function fillBlanks(FamilyMember $primary, FamilyMember $duplicate): void
    {
        $updates = [];

        foreach (self::FILLABLE_FIELDS as $field) {
            if ($this->isEmpty($primary->{$field}) && ! $this->isEmpty($duplicate->{$field})) {
                $updates[$field] = $duplicate->{$field};
            }
        }

        if ($primary->is_alive && ! $duplicate->is_alive && $primary->death_date === null) {
            $updates['is_alive'] = false;
            $updates['death_date'] = $duplicate->death_date;
            $updates['death_place'] = $duplicate->death_place;
        }

        if ($updates !== []) {
            $primary->fill($updates)->save();
        }
    }

    private function reassignRelationships(FamilyMember $primary, FamilyMember $duplicate): void
    {
        MemberRelationship::query()->where('source_member_id', $duplicate->id)
            ->update(['source_member_id' => $primary->id]);
        MemberRelationship::query()->where('target_member_id', $duplicate->id)
            ->update(['target_member_id' => $primary->id]);

        MemberRelationship::query()
            ->where('source_member_id', $primary->id)
            ->where('target_member_id', $primary->id)
            ->delete();

        $seen = [];
        $edgeQuery = MemberRelationship::query()
            ->where('family_id', $primary->family_id)
            ->where(fn (Builder $query) => $query->where('source_member_id', $primary->id)->orWhere('target_member_id', $primary->id));
        $edgeQuery->orderBy('id');
        $edges = $edgeQuery->get();

        foreach ($edges as $edge) {
            $key = $edge->source_member_id.'|'.$edge->target_member_id.'|'.$edge->relationship_type;

            if (isset($seen[$key])) {
                $edge->delete();

                continue;
            }

            $seen[$key] = true;
        }
    }

    private function reassignPhotoTags(FamilyMember $primary, FamilyMember $duplicate): void
    {
        $photoIds = DB::table('member_photo_tags')->where('family_member_id', $duplicate->id)->pluck('member_photo_id');

        foreach ($photoIds as $photoId) {
            DB::table('member_photo_tags')->where('member_photo_id', $photoId)->where('family_member_id', $duplicate->id)->delete();
            DB::table('member_photo_tags')->updateOrInsert(
                ['member_photo_id' => $photoId, 'family_member_id' => $primary->id],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    private function reassignInvitations(FamilyMember $primary, FamilyMember $duplicate): void
    {
        MemberAccountInvitation::query()
            ->where('family_member_id', $duplicate->id)
            ->update(['family_member_id' => $primary->id]);
    }

    private function normalizeName(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $value = strtolower($name);
        $value = (string) preg_replace('/[^a-z0-9\s]/', ' ', $value);
        $value = (string) preg_replace('/\s+/', ' ', $value);
        $tokens = array_filter(
            explode(' ', trim($value)),
            fn (string $token): bool => $token !== '' && ! in_array($token, self::NAME_STOPWORDS, true),
        );

        $normalized = implode(' ', $tokens);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @return array<int, string>
     */
    private function reasons(FamilyMember $primary, FamilyMember $duplicate): array
    {
        $reasons = ['same_name'];

        if ($this->sameBirthDate($primary, $duplicate)) {
            $reasons[] = 'same_birth_date';
        } elseif ($primary->birth_date !== null && $duplicate->birth_date !== null) {
            $reasons[] = 'different_birth_date';
        }

        return $reasons;
    }

    private function confidence(FamilyMember $primary, FamilyMember $duplicate): string
    {
        if ($this->sameBirthDate($primary, $duplicate)) {
            return 'high';
        }

        if ($primary->birth_date !== null && $duplicate->birth_date !== null) {
            return 'low';
        }

        return 'medium';
    }

    private function sameBirthDate(FamilyMember $primary, FamilyMember $duplicate): bool
    {
        return $primary->birth_date !== null
            && $duplicate->birth_date !== null
            && $primary->birth_date->isSameDay($duplicate->birth_date);
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
