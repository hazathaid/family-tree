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
 * @property int $anchor_member_id
 * @property int $granted_by
 * @property int|null $grantee_user_id
 * @property string|null $invited_email
 * @property string $token_hash
 * @property bool $can_view_living
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $last_accessed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Family $family
 * @property-read FamilyMember $anchorMember
 * @property-read User $grantedBy
 * @property-read User|null $grantee
 */
class FamilyViewGrant extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'family_id',
        'anchor_member_id',
        'granted_by',
        'grantee_user_id',
        'invited_email',
        'token_hash',
        'can_view_living',
        'expires_at',
        'revoked_at',
        'last_accessed_at',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'can_view_living' => 'boolean',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_accessed_at' => 'datetime',
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

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function status(): string
    {
        if ($this->isRevoked()) {
            return 'revoked';
        }

        return $this->isExpired() ? 'expired' : 'active';
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function anchorMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'anchor_member_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function grantee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'grantee_user_id');
    }
}
