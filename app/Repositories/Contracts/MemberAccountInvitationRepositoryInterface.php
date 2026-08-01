<?php

namespace App\Repositories\Contracts;

use App\Models\FamilyMember;
use App\Models\MemberAccountInvitation;

interface MemberAccountInvitationRepositoryInterface
{
    public function create(array $attributes): MemberAccountInvitation;

    public function findPendingByTokenHash(string $tokenHash): ?MemberAccountInvitation;

    public function expirePendingFor(FamilyMember $member): void;

    public function accept(MemberAccountInvitation $invitation): MemberAccountInvitation;

    public function latestFor(FamilyMember $member): ?MemberAccountInvitation;
}
