<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\FamilyUserRole;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->status === 'active';
    }

    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    public function view(User $user, Event $event): bool
    {
        return $this->role($user, $event) !== null;
    }

    public function update(User $user, Event $event): bool
    {
        return $event->organizer_id === $user->id || in_array($this->role($user, $event), [FamilyUserRole::ROLE_OWNER, FamilyUserRole::ROLE_ADMIN], true);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function rsvp(User $user, Event $event): bool
    {
        return $this->view($user, $event);
    }

    private function role(User $user, Event $event): ?string
    {
        return $event->family->userRoles()->where('user_id', $user->id)->value('role');
    }
}
