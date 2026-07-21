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
 * @property int $family_id
 * @property int|null $user_id
 * @property string $activity_type
 * @property array<string, mixed> $payload
 * @property Carbon|null $created_at
 * @property-read Family $family
 * @property-read User|null $user
 */
class ActivityLog extends Model
{
    use HasFactory, HasUuids;

    public const MEMBER_CREATED = 'MEMBER_CREATED';

    public const ARTICLE_CREATED = 'ARTICLE_CREATED';

    public const PHOTO_UPLOADED = 'PHOTO_UPLOADED';

    public const EVENT_CREATED = 'EVENT_CREATED';

    public const FILTERS = [
        'members' => [self::MEMBER_CREATED],
        'articles' => [self::ARTICLE_CREATED],
        'photos' => [self::PHOTO_UPLOADED],
        'events' => [self::EVENT_CREATED],
    ];

    protected $fillable = ['uuid', 'family_id', 'user_id', 'activity_type', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
