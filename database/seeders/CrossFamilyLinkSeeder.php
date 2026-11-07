<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\FamilyLink;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class CrossFamilyLinkSeeder extends Seeder
{
    private const DATA_PATH = __DIR__.'/data/cross_family_link_data.php';

    private string $namespace = 'family-tree:cross-family-link:';

    public function run(): void
    {
        $data = require self::DATA_PATH;

        if (! is_array($data)) {
            throw new RuntimeException('Data tautan lintas-keluarga tidak valid.');
        }

        DB::transaction(function () use ($data): void {
            $familyData = $this->requiredArray($data, 'family');
            $ownerData = $this->requiredArray($data, 'owner');

            $this->namespace = 'family-tree:cross-family-link:'.$this->requiredString($familyData, 'slug').':';

            $owner = $this->ownerUser($ownerData);
            $family = $this->family($familyData, $owner);
            $this->ensureOwnerRole($family, $owner);

            $members = $this->members($this->requiredArray($data, 'members'), $family, $owner);
            $this->relationships($data['relationships'] ?? [], $family, $members);

            $linkData = $this->requiredArray($data, 'link');
            $this->link($linkData, $family, $owner, $members);
        });
    }

    private function ownerUser(array $data): User
    {
        $email = $this->requiredString($data, 'email');

        $user = User::query()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->fill([
                'name' => $this->requiredString($data, 'name'),
                'phone' => $this->optionalString($data['phone'] ?? null),
                'password' => $this->optionalString($data['password'] ?? null) ?? 'password',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }

        $user->save();

        return $user;
    }

    private function family(array $data, User $owner): Family
    {
        $family = Family::withTrashed()->firstOrNew([
            'slug' => $this->requiredString($data, 'slug'),
        ]);

        $family->fill([
            'name' => $this->requiredString($data, 'name'),
            'origin_city' => $this->optionalString($data['origin_city'] ?? null),
            'description' => $this->optionalString($data['description'] ?? null),
            'created_by' => $owner->id,
        ]);

        $family->save();

        if ($family->trashed()) {
            $family->restore();
        }

        return $family;
    }

    private function ensureOwnerRole(Family $family, User $owner): void
    {
        $role = FamilyUserRole::withTrashed()->firstOrNew([
            'family_id' => $family->id,
            'user_id' => $owner->id,
        ]);

        $role->fill([
            'uuid' => $role->uuid ?: $this->uuid('role:owner:'.$owner->email),
            'role' => FamilyUserRole::ROLE_OWNER,
        ]);

        $role->save();

        if ($role->trashed()) {
            $role->restore();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, FamilyMember>
     */
    private function members(array $rows, Family $family, User $owner): array
    {
        $members = [];
        $keys = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new RuntimeException('Setiap anggota keluarga harus berupa array.');
            }

            $key = $this->requiredString($row, 'key');
            $fullName = $this->requiredString($row, 'full_name');
            $isAlive = (bool) ($row['is_alive'] ?? true);

            if (isset($keys[$key])) {
                throw new RuntimeException("Key anggota \"{$key}\" dipakai lebih dari sekali.");
            }
            $keys[$key] = true;

            $member = FamilyMember::withTrashed()->firstOrNew([
                'uuid' => $this->uuid('member:'.$key),
            ]);

            $member->fill([
                'family_id' => $family->id,
                'family_branch_id' => null,
                'full_name' => $fullName,
                'nickname' => $this->optionalString($row['nickname'] ?? null),
                'gender' => $this->optionalString($row['gender'] ?? null),
                'birth_date' => $this->optionalString($row['birth_date'] ?? null),
                'birth_place' => $this->optionalString($row['birth_place'] ?? null),
                'is_alive' => $isAlive,
                'death_date' => $isAlive ? null : $this->optionalString($row['death_date'] ?? null),
                'death_place' => $isAlive ? null : $this->optionalString($row['death_place'] ?? null),
                'biography' => $this->optionalString($row['biography'] ?? null),
                'created_by' => $owner->id,
            ]);

            $member->save();

            if ($member->trashed()) {
                $member->restore();
            }

            $members[$key] = $member;
        }

        return $members;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, FamilyMember>  $members
     */
    private function relationships(array $rows, Family $family, array $members): void
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new RuntimeException('Setiap relasi harus berupa array.');
            }

            $sourceKey = $this->requiredString($row, 'source');
            $targetKey = $this->requiredString($row, 'target');
            $type = $this->requiredString($row, 'type');

            if (! isset($members[$sourceKey])) {
                throw new RuntimeException("Anggota sumber \"{$sourceKey}\" tidak ditemukan pada daftar members.");
            }

            if (! isset($members[$targetKey])) {
                throw new RuntimeException("Anggota tujuan \"{$targetKey}\" tidak ditemukan pada daftar members.");
            }

            if (! in_array($type, MemberRelationship::TYPES, true)) {
                throw new RuntimeException("Tipe relasi \"{$type}\" tidak dikenal.");
            }

            $relationship = MemberRelationship::withTrashed()->firstOrNew([
                'uuid' => $this->uuid("relationship:{$sourceKey}:{$targetKey}:{$type}"),
            ]);

            $relationship->fill([
                'family_id' => $family->id,
                'source_member_id' => $members[$sourceKey]->id,
                'target_member_id' => $members[$targetKey]->id,
                'relationship_type' => $type,
            ]);

            $relationship->save();

            if ($relationship->trashed()) {
                $relationship->restore();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, FamilyMember>  $members
     */
    private function link(array $data, Family $family, User $owner, array $members): void
    {
        $memberKey = $this->requiredString($data, 'family_member_key');

        if (! isset($members[$memberKey])) {
            throw new RuntimeException("Anggota \"{$memberKey}\" untuk tautan tidak ditemukan pada daftar members.");
        }

        $member = $members[$memberKey];

        $partnerData = $this->requiredArray($data, 'partner_member');
        $partnerFamily = Family::query()
            ->where('slug', $this->requiredString($partnerData, 'family_slug'))
            ->first();

        if ($partnerFamily === null) {
            throw new RuntimeException('Keluarga partner tidak ditemukan. Jalankan seeder keluarga partner terlebih dahulu.');
        }

        $partnerName = $this->requiredString($partnerData, 'full_name');
        $partnerMember = FamilyMember::query()
            ->where('family_id', $partnerFamily->id)
            ->where('full_name', $partnerName)
            ->first();

        if ($partnerMember === null) {
            throw new RuntimeException("Anggota partner \"{$partnerName}\" tidak ditemukan di keluarga \"{$partnerFamily->slug}\".");
        }

        $status = $this->optionalString($data['status'] ?? null) ?? FamilyLink::STATUS_PENDING;

        if (! in_array($status, [
            FamilyLink::STATUS_PENDING,
            FamilyLink::STATUS_ACTIVE,
            FamilyLink::STATUS_REVOKED,
            FamilyLink::STATUS_EXPIRED,
        ], true)) {
            throw new RuntimeException("Status tautan \"{$status}\" tidak dikenal.");
        }

        $requestedBy = $this->userByEmail($data['requested_by_email'] ?? null) ?? $owner;
        $approvedByFamily = $this->userByEmail($data['approved_by_family_email'] ?? null) ?? $owner;
        $approvedByLinkedFamily = $this->userByEmail($data['approved_by_linked_family_email'] ?? null)
            ?? $this->ownerUserForFamily($partnerFamily);

        if ($status === FamilyLink::STATUS_ACTIVE && $approvedByLinkedFamily === null) {
            throw new RuntimeException('Tautan berstatus active membutuhkan approver dari keluarga partner.');
        }

        $link = FamilyLink::withTrashed()->firstOrNew([
            'uuid' => $this->uuid("link:{$family->slug}:{$memberKey}:{$partnerFamily->slug}:{$partnerName}"),
        ]);

        $link->fill([
            'family_id' => $family->id,
            'linked_family_id' => $partnerFamily->id,
            'member_id' => $member->id,
            'linked_member_id' => $partnerMember->id,
            'link_type' => FamilyLink::LINK_TYPE_MARRIAGE,
            'children_home_family_id' => $this->childrenHomeFamilyId($data['children_home_family'] ?? null, $family, $partnerFamily),
            'status' => $status,
            'requested_by' => $requestedBy->id,
            'approved_by_family' => $approvedByFamily->id,
            'approved_by_linked_family' => $approvedByLinkedFamily?->id,
            'expires_at' => $this->optionalDate($data['expires_at'] ?? null),
            'revoked_at' => $status === FamilyLink::STATUS_REVOKED ? now() : null,
        ]);

        $link->save();

        if ($link->trashed()) {
            $link->restore();
        }
    }

    private function childrenHomeFamilyId(mixed $value, Family $family, Family $partnerFamily): ?int
    {
        return match ($this->optionalString($value)) {
            'second' => $family->id,
            'partner' => $partnerFamily->id,
            default => null,
        };
    }

    private function userByEmail(mixed $email): ?User
    {
        $email = $this->optionalString($email);

        if ($email === null) {
            return null;
        }

        return User::query()->where('email', $email)->where('status', 'active')->first();
    }

    private function ownerUserForFamily(Family $family): ?User
    {
        $role = FamilyUserRole::query()
            ->where('family_id', $family->id)
            ->where('role', FamilyUserRole::ROLE_OWNER)
            ->first();

        return $role?->user ?? $family->creator;
    }

    /**
     * @return array<string, mixed>
     */
    private function requiredArray(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            throw new RuntimeException("Bagian \"{$key}\" wajib diisi dan harus berupa array.");
        }

        return $value;
    }

    private function requiredString(array $data, string $key): string
    {
        $value = $this->optionalString($data[$key] ?? null);

        if ($value === null) {
            throw new RuntimeException("Kolom \"{$key}\" wajib diisi.");
        }

        return $value;
    }

    private function optionalString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function optionalDate(mixed $value): ?Carbon
    {
        $value = $this->optionalString($value);

        return $value === null ? null : Carbon::parse($value);
    }

    private function uuid(string $value): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, $this->namespace.$value)->toString();
    }
}
