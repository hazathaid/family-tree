<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyFamilyOfUpdate implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly int $familyId,
        public readonly string $type,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
        public readonly ?int $exceptUserId = null,
        public readonly string $category = NotificationService::CATEGORY_FAMILY_UPDATES,
    ) {}

    public function handle(NotificationService $notifications): void
    {
        User::query()
            ->where('status', 'active')
            ->whereHas('familyRoles', fn ($query) => $query->where('family_id', $this->familyId))
            ->when($this->exceptUserId, fn ($query, int $except) => $query->where('id', '!=', $except))
            ->chunkById(100, function ($users) use ($notifications): void {
                foreach ($users as $user) {
                    $notifications->dispatchForUser($user, $this->category, $this->type, $this->title, $this->body, $this->data);
                }
            });
    }
}
