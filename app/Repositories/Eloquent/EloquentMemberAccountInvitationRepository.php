<?php

namespace App\Repositories\Eloquent;

use App\Models\FamilyMember;
use App\Models\MemberAccountInvitation;
use App\Repositories\Contracts\MemberAccountInvitationRepositoryInterface;

class EloquentMemberAccountInvitationRepository implements MemberAccountInvitationRepositoryInterface
{
    public function create(array $attributes): MemberAccountInvitation
    {
        return MemberAccountInvitation::query()->create($attributes)->load('member.family');
    }

    public function findPendingByTokenHash(string $tokenHash): ?MemberAccountInvitation
    {
        $invitation = MemberAccountInvitation::query()
            ->with('member.family')
            ->where('token_hash', $tokenHash)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        return $invitation instanceof MemberAccountInvitation ? $invitation : null;
    }

    public function expirePendingFor(FamilyMember $member): void
    {
        MemberAccountInvitation::query()
            ->where('family_member_id', $member->id)
            ->whereNull('accepted_at')
            ->delete();
    }

    public function accept(MemberAccountInvitation $invitation): MemberAccountInvitation
    {
        $invitation->forceFill(['accepted_at' => now()])->save();

        return $invitation->refresh();
    }

    public function latestFor(FamilyMember $member): ?MemberAccountInvitation
    {
        return MemberAccountInvitation::withTrashed()
            ->where('family_member_id', $member->id)
            ->latest('id')
            ->first();
    }
}
