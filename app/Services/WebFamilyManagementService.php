<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Repositories\Contracts\FamilyBranchRepositoryInterface;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;
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
    ) {}

    public function settings(Family $family): array
    {
        return [
            'branches' => $this->branches->paginateForFamily($family, 10),
            'memberships' => $this->roles->activeForFamily($family),
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
            'relationships' => $this->relationships->forMember($member),
            'relationship_to_viewer' => $relationship,
        ];
    }

    public function branchesForForm(Family $family): Collection
    {
        return collect($this->branches->paginateForFamily($family, 100)->items())->sortBy('name')->values();
    }
}
