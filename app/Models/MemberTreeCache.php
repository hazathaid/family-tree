<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<string, mixed> $tree_json
 */
class MemberTreeCache extends Model
{
    use HasUuids;

    protected $table = 'member_tree_cache';

    protected $fillable = ['uuid', 'family_id', 'member_id', 'mode', 'depth', 'tree_json', 'generated_at', 'expires_at'];

    protected $casts = ['depth' => 'integer', 'tree_json' => 'array', 'generated_at' => 'datetime', 'expires_at' => 'datetime'];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
