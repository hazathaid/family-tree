<?php

namespace App\Notifications;

use App\Models\FamilyViewGrant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FamilyViewGrantNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly FamilyViewGrant $grant,
        public readonly ?string $plainToken = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $family = $this->grant->family;
        $anchor = $this->grant->anchorMember;
        $action = $this->plainToken !== null
            ? route('family-view.accept', ['token' => $this->plainToken])
            : route('family-view.show', $family);

        return (new MailMessage)
            ->subject('Akses baca-saja silsilah keluarga')
            ->greeting('Halo,')
            ->line('Keluarga '.$family->name.' memberikan akses baca-saja kepada Anda untuk melihat silsilah '.$anchor->full_name.'.')
            ->action('Buka silsilah', $action)
            ->line('Akses ini berlaku sampai '.$this->grant->expires_at->translatedFormat('d F Y H:i').'.')
            ->line('Anggota yang masih hidup akan disamarkan. Akses dapat dicabut oleh keluarga kapan saja.');
    }
}
