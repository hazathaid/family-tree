<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\MemberAccountInvitation;
use App\Models\User;
use App\Notifications\MemberAccountInvitationNotification;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;
use App\Repositories\Contracts\MemberAccountInvitationRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MemberAccountInvitationService
{
    public function __construct(
        private readonly MemberAccountInvitationRepositoryInterface $invitations,
        private readonly FamilyMemberRepositoryInterface $members,
        private readonly FamilyUserRoleRepositoryInterface $roles,
        private readonly ActivityLogService $activityLog,
    ) {}

    /** @return array{invitation: MemberAccountInvitation, plain_token: string} */
    public function invite(User $actor, FamilyMember $member, string $email): array
    {
        if ($member->user_id !== null) {
            throw ValidationException::withMessages(['member' => ['Profil anggota ini sudah memiliki akun.']]);
        }

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => ['Email ini sudah terdaftar. Gunakan pengelolaan akses untuk akun yang ada.']]);
        }

        $plainToken = Str::random(64);

        $invitation = DB::transaction(function () use ($actor, $member, $email, $plainToken): MemberAccountInvitation {
            $this->invitations->expirePendingFor($member);

            return $this->invitations->create([
                'family_member_id' => $member->id,
                'invited_by' => $actor->id,
                'email' => Str::lower(trim($email)),
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addDays(7),
            ]);
        });

        Notification::route('mail', $invitation->email)
            ->notify(new MemberAccountInvitationNotification($invitation, $plainToken));
        $this->activityLog->memberAccountInvited($actor, $member);

        return ['invitation' => $invitation, 'plain_token' => $plainToken];
    }

    public function findByPlainToken(string $plainToken): ?MemberAccountInvitation
    {
        return $this->invitations->findPendingByTokenHash(hash('sha256', $plainToken));
    }

    public function latestFor(FamilyMember $member): ?MemberAccountInvitation
    {
        return $this->invitations->latestFor($member);
    }

    public function accept(string $plainToken, array $data): User
    {
        return DB::transaction(function () use ($plainToken, $data): User {
            $invitation = $this->findByPlainToken($plainToken);

            if (! $invitation instanceof MemberAccountInvitation) {
                throw ValidationException::withMessages(['invitation' => ['Undangan tidak valid atau sudah kedaluwarsa.']]);
            }

            $member = FamilyMember::query()
                ->where('id', $invitation->family_member_id)
                ->lockForUpdate()
                ->first();

            if (! $member instanceof FamilyMember) {
                throw (new ModelNotFoundException)->setModel(FamilyMember::class, [$invitation->family_member_id]);
            }

            if ($member->user_id !== null || User::query()->where('email', $invitation->email)->exists()) {
                throw ValidationException::withMessages(['invitation' => ['Undangan tidak dapat digunakan lagi.']]);
            }

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $invitation->email,
                'password' => $data['password'],
                'email_verified_at' => now(),
                'status' => 'active',
            ]);

            $this->members->update($member, ['user_id' => $user->id]);
            $this->roles->restoreOrCreate($member->family, $user, FamilyUserRole::ROLE_MEMBER);
            $this->invitations->accept($invitation);
            $this->activityLog->memberAccountClaimed($user, $member);

            return $user;
        });
    }
}
