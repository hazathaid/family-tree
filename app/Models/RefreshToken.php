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
 * @property int $user_id
 * @property int|null $access_token_id
 * @property string $token_hash
 * @property string|null $device_name
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property-read User $user
 */
class RefreshToken extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['uuid', 'user_id', 'access_token_id', 'token_hash', 'device_name', 'expires_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'access_token_id');
    }
}
