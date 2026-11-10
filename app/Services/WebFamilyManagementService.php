<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyViewGrant;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Repositories\Contracts\FamilyBranchRepositoryInterface;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;
use App\Repositories\Contracts\FamilyViewGrantRepositoryInterface;
use App\Repositories\Contracts\RelationshipRepositoryInterface;
use Illuminate\Support\Collection;

class WebFamilyManagementService
{
    public function __construct(
        private readonly FamilyBranchRepositoryInterface $branches,
        private readonly FamilyMemberRepositoryInterface $members,
        private readonly FamilyUserRoleRepositoryInterface $roles,
        private readonly RelationshipRepositoryInterface $relationships,
        private readonly RelationshipResolverService $relationshipResolver,
        private readonly FamilyViewGrantRepositoryInterface $viewGrants,
    ) {}

    public function settings(Family $family): array
    {
        return [
            'branches' => $this->branches->paginateForFamily($family, 10),
            'memberships' => $this->roles->activeForFamily($family),
        ];
    }

    /**
     * @return array{familyViewGrants: Collection<int, FamilyViewGrant>, anchorOptions: Collection<int, FamilyMember>}
     */
    public function familyViewContext(Family $family): array
    {
        return [
            'familyViewGrants' => $this->viewGrants->listForFamily($family->id),
            'anchorOptions' => $this->members->candidateMembersForFamily($family),
        ];
    }

    public function directory(Family $family, array $filters): array
    {
        return [
            'members' => $this->members->paginateForFamily($family, $filters),
            'branches' => collect($this->branches->paginateForFamily($family, 100)->items()),
            'filters' => $filters,
        ];
    }

    public function memberDetail(FamilyMember $member, User $user): array
    {
        $viewer = $this->members->findForUserInFamily($user, $member->family);
        $relationship = null;

        if ($viewer instanceof FamilyMember) {
            $relationship = $this->relationshipResolver->resolve($viewer, $member)['relationship'];
        }

        return [
            'relationships' => $this->basicRelationships($member),
            'relationship_to_viewer' => $relationship,
        ];
    }

    /**
     * @return Collection<int, array{member: FamilyMember, label: string}>
     */
    private function basicRelationships(FamilyMember $member): Collection
    {
        return $this->relationships->forMember($member)
            ->map(function (MemberRelationship $relationship) use ($member): array {
                $memberIsSource = $relationship->source_member_id === $member->id;
                $other = $memberIsSource ? $relationship->targetMember : $relationship->sourceMember;
                $type = $memberIsSource
                    ? $this->inverseRelationshipType($relationship->relationship_type, $other)
                    : $relationship->relationship_type;

                return [
                    'member' => $other,
                    'label' => ucfirst($type),
                ];
            })
            ->unique(fn (array $row): string => $row['member']->id.'|'.$row['label'])
            ->values();
    }

    private function inverseRelationshipType(string $type, FamilyMember $other): string
    {
        return match ($type) {
            MemberRelationship::TYPE_FATHER, MemberRelationship::TYPE_MOTHER => MemberRelationship::TYPE_CHILD,
            MemberRelationship::TYPE_CHILD => match ($other->gender) {
                'male' => MemberRelationship::TYPE_FATHER,
                'female' => MemberRelationship::TYPE_MOTHER,
                default => 'parent',
            },
            MemberRelationship::TYPE_HUSBAND => MemberRelationship::TYPE_WIFE,
            MemberRelationship::TYPE_WIFE => MemberRelationship::TYPE_HUSBAND,
            default => $type,
        };
    }

    public function branchesForForm(Family $family): Collection
    {
        return collect($this->branches->paginateForFamily($family, 100)->items())->sortBy('name')->values();
    }
}
