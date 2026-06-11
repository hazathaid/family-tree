<?php

namespace App\Services;

use App\DTOs\FamilyRoleData;
use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilyRoleService
{
    public function __construct(
        private readonly FamilyUserRoleRepositoryInterface $familyRoles,
        private readonly UserRepositoryInterface $users,
        private readonly FamilyRoleCatalogService $roleCatalog,
    ) {}

    public function list(Family $family): Collection
    {
        return $this->familyRoles->activeForFamily($family);
    }

    public function invite(Family $family, FamilyRoleData $data): FamilyUserRole
    {
        return DB::transaction(function () use ($family, $data): FamilyUserRole {
            $user = $this->users->findByEmail($data->email);

            if (! $user instanceof User) {
                throw ValidationException::withMessages([
                    'email' => ['The selected user does not exist.'],
                ]);
            }

            $membership = $this->familyRoles->findActive($family, $user);

            if ($membership instanceof FamilyUserRole) {
                throw ValidationException::withMessages([
                    'email' => ['This user is already an active family member.'],
                ]);
            }

            $membership = $this->familyRoles->restoreOrCreate($family, $user, $data->role);
            $this->roleCatalog->syncUserGlobalRoles($user);

            return $membership->load('user');
        });
    }

    public function assignRole(Family $family, FamilyUserRole $membership, string $role): FamilyUserRole
    {
        $this->ensureMembershipBelongsToFamily($family, $membership);

        return DB::transaction(function () use ($family, $membership, $role): FamilyUserRole {
            if ($membership->role === FamilyUserRole::ROLE_OWNER && $role !== FamilyUserRole::ROLE_OWNER) {
                $this->ensureNotLastOwner($family);
            }

            $updated = $this->familyRoles->update($membership, ['role' => $role]);
            $this->roleCatalog->syncUserGlobalRoles($updated->user);

            return $updated->load('user');
        });
    }

    public function removeMember(Family $family, FamilyUserRole $membership): void
    {
        $this->ensureMembershipBelongsToFamily($family, $membership);

        DB::transaction(function () use ($family, $membership): void {
            if ($membership->role === FamilyUserRole::ROLE_OWNER) {
                $this->ensureNotLastOwner($family);
            }

            $user = $membership->user;
            $this->familyRoles->delete($membership);
            $this->roleCatalog->syncUserGlobalRoles($user);
        });
    }

    private function ensureMembershipBelongsToFamily(Family $family, FamilyUserRole $membership): void
    {
        if ($membership->family_id !== $family->id) {
            throw ValidationException::withMessages([
                'membership' => ['The selected member does not belong to this family.'],
            ]);
        }
    }

    private function ensureNotLastOwner(Family $family): void
    {
        if ($this->familyRoles->countOwners($family) <= 1) {
            throw ValidationException::withMessages([
                'role' => ['The last Owner cannot be removed or changed.'],
            ]);
        }
    }
}
