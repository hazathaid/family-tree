<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EventReminderService
{
    public function __construct(private readonly EventRepositoryInterface $events, private readonly NotificationService $notifications) {}

    public function sendDueReminders(): int
    {
        $sent = 0;
        foreach ($this->events->dueForReminder() as $event) {
            $sent += $this->send($event);
        }

        return $sent;
    }

    public function send(Event $event): int
    {
        return DB::transaction(function () use ($event): int {
            /** @var Event $event */
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            if ($event->reminder_sent_at || $event->event_date->isPast() || $event->event_date->isAfter(now()->addDay())) {
                return 0;
            }

            $title = 'Pengingat acara: '.$event->title;
            $body = $event->title.' akan berlangsung pada '.$event->event_date->format('d-m-Y H:i').($event->location ? ' di '.$event->location : '').'.';
            $userIds = User::query()->where('status', 'active')->whereHas('familyRoles', fn ($query) => $query->where('family_id', $event->family_id))->pluck('id');
            foreach ($userIds as $userId) {
                $this->notifications->notifyEvent($userId, $event, $title, $body);
            }
            $event->update(['reminder_sent_at' => now()]);

            return $userIds->count();
        });
    }
}
