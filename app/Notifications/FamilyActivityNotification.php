<?php

namespace App\Notifications;

use App\Models\Notification as NotificationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FamilyActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly NotificationModel $notification) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->notification->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->notification->body);
    }
}
