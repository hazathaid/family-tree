<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $family_id
 * @property int $linked_family_id
 * @property int $member_id
 * @property int $linked_member_id
 * @property string $link_type
 * @property int|null $children_home_family_id
 * @property string $status
 * @property int $requested_by
 * @property int|null $approved_by_family
 * @property int|null $approved_by_linked_family
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Family $family
 * @property-read Family $linkedFamily
 * @property-read Family|null $childrenHomeFamily
 * @property-read FamilyMember $member
 * @property-read FamilyMember $linkedMember
 * @property-read User $requester
 * @property-read User|null $familyApprover
 * @property-read User|null $linkedFamilyApprover
 */
class FamilyLink extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    public const LINK_TYPE_MARRIAGE = 'marriage';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_EXPIRED = 'expired';

    public const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_ACTIVE];

    protected $fillable = [
        'uuid',
        'family_id',
        'linked_family_id',
        'member_id',
        'linked_member_id',
        'link_type',
        'children_home_family_id',
        'status',
        'requested_by',
        'approved_by_family',
        'approved_by_linked_family',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function involvesFamily(int $familyId): bool
    {
        return $this->family_id === $familyId || $this->linked_family_id === $familyId;
    }

    public function counterFamilyId(int $familyId): int
    {
        return $this->family_id === $familyId ? $this->linked_family_id : $this->family_id;
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    public function linkedFamily(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'linked_family_id');
    }

    public function childrenHomeFamily(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'children_home_family_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'member_id');
    }

    public function linkedMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'linked_member_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function familyApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_family');
    }

    public function linkedFamilyApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_linked_family');
    }
}
