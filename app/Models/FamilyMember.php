<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $family_id
 * @property int|null $family_branch_id
 * @property string $full_name
 * @property string|null $nickname
 * @property string|null $gender
 * @property Carbon|null $birth_date
 * @property string|null $birth_place
 * @property bool $is_alive
 * @property Carbon|null $death_date
 * @property string|null $death_place
 * @property string|null $biography
 * @property string|null $profile_photo
 * @property string|null $profile_photo_thumbnail
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Family $family
 * @property-read FamilyBranch|null $branch
 * @property-read User $creator
 */
class FamilyMember extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'family_id',
        'family_branch_id',
        'full_name',
        'nickname',
        'gender',
        'birth_date',
        'birth_place',
        'is_alive',
        'death_date',
        'death_place',
        'biography',
        'profile_photo',
        'profile_photo_thumbnail',
        'created_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
        'is_alive' => 'boolean',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(FamilyBranch::class, 'family_branch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function taggedPhotos(): BelongsToMany
    {
        return $this->belongsToMany(MemberPhoto::class, 'member_photo_tags')->withTimestamps();
    }
}
