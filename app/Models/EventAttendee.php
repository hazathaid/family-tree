<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $event_id
 * @property int $user_id
 * @property string $status
 * @property-read Event $event
 * @property-read User $user
 */
class EventAttendee extends Model
{
    use HasFactory, HasUuids;

    public const STATUSES = ['yes', 'no', 'maybe'];

    protected $fillable = ['uuid', 'event_id', 'user_id', 'status'];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
