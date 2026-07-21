<?php

namespace App\Models;

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
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $origin_city
 * @property string|null $logo
 * @property string|null $cover_image
 * @property int $created_by
 * @property-read User|null $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Family extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'origin_city',
        'logo',
        'cover_image',
        'created_by',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(FamilyUserRole::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(FamilyBranch::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function photoAlbums(): HasMany
    {
        return $this->hasMany(PhotoAlbum::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(MemberPhoto::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
