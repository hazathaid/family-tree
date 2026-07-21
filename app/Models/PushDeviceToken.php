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
 * @property int $user_id
 * @property string $platform
 * @property string $token
 * @property bool $is_active
 * @property Carbon|null $last_used_at
 */
class PushDeviceToken extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const PLATFORMS = ['android', 'ios'];

    protected $fillable = ['uuid', 'user_id', 'platform', 'token', 'is_active', 'last_used_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_used_at' => 'datetime'];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
