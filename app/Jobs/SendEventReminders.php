<?php

namespace App\Jobs;

use App\Services\EventReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEventReminders implements ShouldQueue
{
    use Queueable;

    public function handle(EventReminderService $service): void
    {
        $service->sendDueReminders();
    }
}
