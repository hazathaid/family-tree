<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TreeRelativeMemberService
{
    public function __construct(
        private readonly FamilyMemberService $members,
        private readonly RelationshipService $relationships,
        private readonly ActivityLogService $activityLog,
        private readonly FamilyMemberRepositoryInterface $memberRepository,
        private readonly RelationshipRepositoryInterface $relationshipRepository,
    ) {}

    public function create(User $actor, FamilyMember $member, array $data): FamilyMember
    {
        return DB::transaction(function () use ($actor, $member, $data): FamilyMember {
            $coParent = $this->resolveCoParent($member, $data);
            $biologicalParents = $coParent instanceof FamilyMember
                ? $this->biologicalParents($member, $coParent)
                : null;

            $relative = $this->members->create($actor, $member->family, $data);

            if ($biologicalParents !== null) {
                $this->linkBiologicalParents($member, $biologicalParents, $relative);
            } else {
                $this->relationships->create($this->relationshipData($member, $relative, $data['relation']));
            }

            $this->activityLog->treeRelativeCreated($actor, $member, $relative, $data['relation']);

            return $relative;
        });
    }

    private function resolveCoParent(FamilyMember $member, array $data): ?FamilyMember
    {
        $uuid = $data['co_parent_uuid'] ?? null;

        if ($uuid === null || $uuid === '') {
            return null;
        }

        if (($data['relation'] ?? null) !== 'child') {
            throw ValidationException::withMessages([
                'co_parent_uuid' => ['Pasangan hanya dapat dipilih saat menambahkan anak.'],
            ]);
        }

        $coParent = $this->memberRepository->findByUuid((string) $uuid);

        if (! $coParent instanceof FamilyMember || $coParent->family_id !== $member->family_id) {
            throw ValidationException::withMessages([
                'co_parent_uuid' => ['Pasangan yang dipilih harus berasal dari keluarga yang sama.'],
            ]);
        }

        if ($coParent->id === $member->id) {
            throw ValidationException::withMessages([
                'co_parent_uuid' => ['Anggota dan pasangan tidak boleh orang yang sama.'],
            ]);
        }

        return $coParent;
    }

    /**
     * @return array{0: FamilyMember, 1: FamilyMember}
     */
    private function biologicalParents(FamilyMember $member, FamilyMember $coParent): array
    {
        if (! in_array($member->gender, ['male', 'female'], true)) {
            throw ValidationException::withMessages([
                'relation' => ['Jenis kelamin anggota harus diisi sebelum menambahkan anak.'],
            ]);
        }

        if (! in_array($coParent->gender, ['male', 'female'], true)) {
            throw ValidationException::withMessages([
                'co_parent_uuid' => ['Jenis kelamin pasangan harus diisi sebelum menambahkan anak.'],
            ]);
        }

        if ($member->gender === $coParent->gender) {
            throw ValidationException::withMessages([
                'co_parent_uuid' => ['Anggota dan pasangan harus memiliki jenis kelamin berbeda.'],
            ]);
        }

        if (! $this->relationshipRepository->areSpouses($member->family, $member, $coParent)) {
            throw ValidationException::withMessages([
                'co_parent_uuid' => ['Anggota dan pasangan harus terhubung sebagai suami-istri terlebih dahulu.'],
            ]);
        }

        return $member->gender === 'male'
            ? [$member, $coParent]
            : [$coParent, $member];
    }

    /**
     * @param  array{0: FamilyMember, 1: FamilyMember}  $parents
     */
    private function linkBiologicalParents(FamilyMember $member, array $parents, FamilyMember $child): void
    {
        [$father, $mother] = $parents;

        foreach ([[$father, MemberRelationship::TYPE_FATHER], [$mother, MemberRelationship::TYPE_MOTHER]] as [$parent, $type]) {
            $this->relationships->create([
                'family_uuid' => $member->family->uuid,
                'source_member_uuid' => $parent->uuid,
                'target_member_uuid' => $child->uuid,
                'relationship_type' => $type,
            ]);
        }
    }

    private function relationshipData(FamilyMember $member, FamilyMember $relative, string $relation): array
    {
        if ($relation === 'parent') {
            return [
                'family_uuid' => $member->family->uuid,
                'source_member_uuid' => $relative->uuid,
                'target_member_uuid' => $member->uuid,
                'relationship_type' => $relative->gender === 'male'
                    ? MemberRelationship::TYPE_FATHER
                    : MemberRelationship::TYPE_MOTHER,
            ];
        }

        if ($relation === 'spouse') {
            return [
                'family_uuid' => $member->family->uuid,
                'source_member_uuid' => $relative->uuid,
                'target_member_uuid' => $member->uuid,
                'relationship_type' => $relative->gender === 'male'
                    ? MemberRelationship::TYPE_HUSBAND
                    : MemberRelationship::TYPE_WIFE,
            ];
        }

        if (! in_array($member->gender, ['male', 'female'], true)) {
            throw ValidationException::withMessages([
                'relation' => ['Jenis kelamin anggota harus diisi sebelum menambahkan anak.'],
            ]);
        }

        return [
            'family_uuid' => $member->family->uuid,
            'source_member_uuid' => $member->uuid,
            'target_member_uuid' => $relative->uuid,
            'relationship_type' => $member->gender === 'male'
                ? MemberRelationship::TYPE_FATHER
                : MemberRelationship::TYPE_MOTHER,
        ];
    }
}
