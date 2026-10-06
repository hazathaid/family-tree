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
 * @property int $family_member_id
 * @property int $uploaded_by
 * @property string $title
 * @property string|null $category
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property Carbon|null $document_date
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property-read Family $family
 * @property-read FamilyMember $member
 * @property-read User $uploader
 */
class MemberDocument extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const CATEGORY_IDENTITY = 'identity';

    public const CATEGORY_FAMILY_CARD = 'family_card';

    public const CATEGORY_CERTIFICATE = 'certificate';

    public const CATEGORY_EDUCATION = 'education';

    public const CATEGORY_LEGAL = 'legal';

    public const CATEGORY_MEDICAL = 'medical';

    public const CATEGORY_OTHER = 'other';

    public const CATEGORIES = [
        self::CATEGORY_IDENTITY,
        self::CATEGORY_FAMILY_CARD,
        self::CATEGORY_CERTIFICATE,
        self::CATEGORY_EDUCATION,
        self::CATEGORY_LEGAL,
        self::CATEGORY_MEDICAL,
        self::CATEGORY_OTHER,
    ];

    protected $fillable = [
        'uuid', 'family_id', 'family_member_id', 'uploaded_by', 'title', 'category',
        'path', 'original_name', 'mime_type', 'size', 'document_date', 'notes',
    ];

    protected function casts(): array
    {
        return ['document_date' => 'date'];
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

    public function member(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'family_member_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
