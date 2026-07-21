<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $family_id
 * @property string $title
 * @property string|null $description
 * @property Carbon $event_date
 * @property string|null $location
 * @property int $organizer_id
 * @property Carbon|null $reminder_sent_at
 * @property-read Family $family
 * @property-read User $organizer
 * @property-read Collection<int, EventAttendee> $attendees
 */
class Event extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['uuid', 'family_id', 'title', 'description', 'event_date', 'location', 'organizer_id', 'reminder_sent_at'];

    protected function casts(): array
    {
        return ['event_date' => 'datetime', 'reminder_sent_at' => 'datetime'];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }
}
