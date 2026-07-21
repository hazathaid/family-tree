<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\MemberPhoto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<MemberPhoto> */
class MemberPhotoFactory extends Factory
{
    public function definition(): array
    {
        return ['uuid' => (string) Str::uuid(), 'family_id' => Family::factory(), 'photo_album_id' => null, 'uploaded_by' => User::factory(), 'path' => 'family-photos/photo.jpg', 'thumbnail_path' => 'family-photos/thumb_photo.jpg', 'original_name' => 'photo.jpg', 'mime_type' => 'image/jpeg', 'size' => 1000, 'width' => 800, 'height' => 600, 'caption' => null, 'captured_at' => null];
    }
}
