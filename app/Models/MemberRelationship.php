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
 * @property int $source_member_id
 * @property int $target_member_id
 * @property string $relationship_type
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Family $family
 * @property-read FamilyMember $sourceMember
 * @property-read FamilyMember $targetMember
 */
class MemberRelationship extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    public const TYPE_FATHER = 'father';

    public const TYPE_MOTHER = 'mother';

    public const TYPE_CHILD = 'child';

    public const TYPE_HUSBAND = 'husband';

    public const TYPE_WIFE = 'wife';

    public const TYPES = [
        self::TYPE_FATHER,
        self::TYPE_MOTHER,
        self::TYPE_CHILD,
        self::TYPE_HUSBAND,
        self::TYPE_WIFE,
    ];

    protected $fillable = [
        'uuid',
        'family_id',
        'source_member_id',
        'target_member_id',
        'relationship_type',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

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

    public function sourceMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'source_member_id');
    }

    public function targetMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'target_member_id');
    }
}
