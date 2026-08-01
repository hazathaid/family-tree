<?php

namespace App\Notifications;

use App\Models\MemberAccountInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberAccountInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly MemberAccountInvitation $invitation,
        public readonly string $plainToken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Undangan akun anggota keluarga')
            ->greeting('Halo '.$this->invitation->member->full_name.',')
            ->line('Anda diundang untuk mengelola profil Anda di keluarga '.$this->invitation->member->family->name.'.')
            ->action('Terima undangan', route('member-account-invitations.show', $this->plainToken))
            ->line('Tautan ini berlaku selama 7 hari dan hanya dapat digunakan satu kali.');
    }
}
