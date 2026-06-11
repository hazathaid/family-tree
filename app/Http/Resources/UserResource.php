<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return [
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar ? $disk->url($user->avatar) : null,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'last_login_at' => $user->last_login_at?->toISOString(),
            'status' => $user->status,
        ];
    }
}
