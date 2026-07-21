<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $code
 * @property string $name
 * @property string $description
 */
class Badge extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'code', 'name', 'description'];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function awards(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }
}
