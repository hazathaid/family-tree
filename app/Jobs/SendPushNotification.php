<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\PushDeviceToken;
use App\Repositories\Contracts\PushDeviceTokenRepositoryInterface;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $notificationId) {}

    public function handle(PushDeviceTokenRepositoryInterface $devices, PushNotificationService $push): void
    {
        $notification = Notification::query()->find($this->notificationId);
        if (! $notification) {
            return;
        }

        foreach ($devices->activeForUser($notification->user_id) as $device) {
            /** @var PushDeviceToken $device */
            $push->send($device, $notification);
        }
    }
}
