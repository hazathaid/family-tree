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

    public const MEMBER_UPDATED = 'MEMBER_UPDATED';

    public const MEMBER_DELETED = 'MEMBER_DELETED';

    public const MEMBER_PHOTO_UPDATED = 'MEMBER_PHOTO_UPDATED';

    public const ARTICLE_CREATED = 'ARTICLE_CREATED';

    public const PHOTO_UPLOADED = 'PHOTO_UPLOADED';

    public const EVENT_CREATED = 'EVENT_CREATED';

    public const MEMBER_ACCOUNT_INVITED = 'MEMBER_ACCOUNT_INVITED';

    public const MEMBER_ACCOUNT_CLAIMED = 'MEMBER_ACCOUNT_CLAIMED';

    public const TREE_RELATIVE_CREATED = 'TREE_RELATIVE_CREATED';

    public const GEDCOM_IMPORTED = 'GEDCOM_IMPORTED';

    public const MEMBERS_IMPORTED = 'MEMBERS_IMPORTED';

    public const FILTERS = [
        'members' => [
            self::MEMBER_CREATED,
            self::MEMBER_UPDATED,
            self::MEMBER_DELETED,
            self::MEMBER_PHOTO_UPDATED,
            self::MEMBER_ACCOUNT_INVITED,
            self::MEMBER_ACCOUNT_CLAIMED,
            self::TREE_RELATIVE_CREATED,
            self::GEDCOM_IMPORTED,
            self::MEMBERS_IMPORTED,
        ],
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
