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
 * @property int $source_member_id
 * @property int $target_member_id
 * @property string|null $relationship_name
 * @property array<int, array<string, mixed>> $relationship_path
 * @property bool $is_connected
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Family $family
 * @property-read FamilyMember $sourceMember
 * @property-read FamilyMember $targetMember
 */
class MemberRelationshipCache extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'member_relationship_cache';

    protected $fillable = [
        'uuid',
        'family_id',
        'source_member_id',
        'target_member_id',
        'relationship_name',
        'relationship_path',
        'is_connected',
        'expires_at',
    ];

    protected $casts = [
        'relationship_path' => 'array',
        'is_connected' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function sourceMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'source_member_id');
    }

    public function targetMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'target_member_id');
    }
}
