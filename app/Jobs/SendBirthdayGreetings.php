<?php

namespace App\Jobs;

use App\Models\FamilyMember;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBirthdayGreetings implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(NotificationService $notifications): int
    {
        $today = now();
        $sent = 0;

        FamilyMember::query()
            ->with('user')
            ->whereNotNull('user_id')
            ->where('is_alive', true)
            ->whereNotNull('birth_date')
            ->whereMonth('birth_date', $today->month)
            ->whereDay('birth_date', $today->day)
            ->chunkById(100, function ($members) use ($notifications, &$sent): void {
                foreach ($members as $member) {
                    $user = $member->user;

                    if (! $user instanceof User) {
                        continue;
                    }

                    $notification = $notifications->dispatchForUser(
                        $user,
                        NotificationService::CATEGORY_FAMILY_UPDATES,
                        'birthday',
                        'Selamat ulang tahun, '.$member->full_name.'!',
                        'Semoga sehat dan bahagia. Keluarga Anda mengingat hari istimewa ini.',
                        ['member_uuid' => $member->uuid, 'target_type' => 'member', 'target_uuid' => $member->uuid],
                    );

                    if ($notification !== null) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }
}
