<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $event_id
 * @property string $type
 * @property string $title
 * @property string $body
 * @property array<string, mixed>|null $data
 * @property bool $is_read
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 */
class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['uuid', 'user_id', 'event_id', 'type', 'title', 'body', 'data', 'is_read', 'read_at'];

    protected function casts(): array
    {
        return ['data' => 'array', 'is_read' => 'boolean', 'read_at' => 'datetime'];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
