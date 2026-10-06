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
 * @property int $user_id
 * @property int $root_member_id
 * @property string $mode
 * @property int $depth
 * @property string $layout
 * @property string $format
 * @property string $paper_size
 * @property string $status
 * @property string|null $path
 * @property string|null $error
 * @property Carbon|null $completed_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Family $family
 * @property-read User $user
 * @property-read FamilyMember $member
 */
class TreeExport extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const FORMAT_PNG = 'png';

    public const FORMAT_PDF = 'pdf';

    public const FORMATS = [self::FORMAT_PNG, self::FORMAT_PDF];

    protected $fillable = [
        'uuid', 'family_id', 'user_id', 'root_member_id', 'mode', 'depth', 'layout',
        'format', 'paper_size', 'status', 'path', 'error', 'completed_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime', 'expires_at' => 'datetime'];
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

    public function member(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'root_member_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
