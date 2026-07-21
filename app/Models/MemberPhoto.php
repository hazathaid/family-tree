<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
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
 * @property int|null $photo_album_id
 * @property int $uploaded_by
 * @property string $path
 * @property string $thumbnail_path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 * @property string|null $caption
 * @property Carbon|null $captured_at
 * @property Carbon|null $created_at
 * @property-read Family $family
 * @property-read PhotoAlbum|null $album
 * @property-read User $uploader
 * @property-read Collection<int, FamilyMember> $taggedMembers
 */
class MemberPhoto extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['uuid', 'family_id', 'photo_album_id', 'uploaded_by', 'path', 'thumbnail_path', 'original_name', 'mime_type', 'size', 'width', 'height', 'caption', 'captured_at'];

    protected $casts = ['captured_at' => 'datetime'];

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

    public function album(): BelongsTo
    {
        return $this->belongsTo(PhotoAlbum::class, 'photo_album_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function taggedMembers(): BelongsToMany
    {
        return $this->belongsToMany(FamilyMember::class, 'member_photo_tags')->withTimestamps();
    }
}
