<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TreeRelativeMemberService
{
    public function __construct(
        private readonly FamilyMemberService $members,
        private readonly RelationshipService $relationships,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function create(User $actor, FamilyMember $member, array $data): FamilyMember
    {
        return DB::transaction(function () use ($actor, $member, $data): FamilyMember {
            $relative = $this->members->create($actor, $member->family, $data);
            $this->relationships->create($this->relationshipData($member, $relative, $data['relation']));
            $this->activityLog->treeRelativeCreated($actor, $member, $relative, $data['relation']);

            return $relative;
        });
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
