<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberPhoto;
use App\Models\PhotoAlbum;
use App\Models\User;
use App\Repositories\Contracts\MemberPhotoRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MemberPhotoService
{
    public function __construct(private readonly MemberPhotoRepositoryInterface $photos) {}

    public function upload(User $user, array $data, UploadedFile $image): MemberPhoto
    {
        $family = Family::query()->where('uuid', $data['family_uuid'])->firstOrFail();
        abort_unless($family->userRoles()->where('user_id', $user->id)->exists(), 403);
        $album = isset($data['album_uuid']) ? PhotoAlbum::query()->where('uuid', $data['album_uuid'])->firstOrFail() : null;
        if ($album && $album->family_id !== $family->id) {
            throw ValidationException::withMessages(['album_uuid' => ['The selected album does not belong to this family.']]);
        }

        $directory = 'family-photos/'.$family->uuid;
        $name = pathinfo($image->hashName(), PATHINFO_FILENAME).'.jpg';
        [$contents, $width, $height] = $this->resize($image, 2048, 85);
        [$thumbnail] = $this->resize($image, 400, 80);
        $path = $directory.'/'.$name;
        $thumbnailPath = $directory.'/thumb_'.$name;
        Storage::disk('public')->put($path, $contents);
        Storage::disk('public')->put($thumbnailPath, $thumbnail);

        return $this->photos->create(['family_id' => $family->id, 'photo_album_id' => $album?->id, 'uploaded_by' => $user->id, 'path' => $path, 'thumbnail_path' => $thumbnailPath, 'original_name' => $image->getClientOriginalName(), 'mime_type' => 'image/jpeg', 'size' => strlen($contents), 'width' => $width, 'height' => $height, 'caption' => $data['caption'] ?? null, 'captured_at' => $data['captured_at'] ?? null]);
    }

    public function tag(MemberPhoto $photo, array $memberUuids): MemberPhoto
    {
        $members = FamilyMember::query()->whereIn('uuid', $memberUuids)->get();
        if ($members->count() !== count($memberUuids) || $members->contains(fn ($member) => $member->family_id !== $photo->family_id)) {
            throw ValidationException::withMessages(['member_uuids' => ['Every tagged member must belong to the photo family.']]);
        }
        $photo->taggedMembers()->sync($members->pluck('id')->all());

        return $this->photos->loadDetails($photo->refresh());
    }

    public function delete(MemberPhoto $photo): void
    {
        Storage::disk('public')->delete([$photo->path, $photo->thumbnail_path]);
        $this->photos->delete($photo);
    }

    private function resize(UploadedFile $image, int $max, int $quality): array
    {
        $sourcePath = $image->getRealPath();
        $info = $sourcePath ? @getimagesize($sourcePath) : false;
        if (! $info || ! function_exists('imagecreatetruecolor')) {
            return [file_get_contents($image->getRealPath()), $info[0] ?? null, $info[1] ?? null];
        }
        $source = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath), 'image/png' => @imagecreatefrompng($sourcePath), 'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false, default => false
        };
        if (! $source) {
            return [file_get_contents($sourcePath), $info[0], $info[1]];
        }
        $ratio = min($max / max($info[0], 1), $max / max($info[1], 1), 1);
        $width = max(1, (int) round($info[0] * $ratio));
        $height = max(1, (int) round($info[1] * $ratio));
        $target = imagecreatetruecolor($width, $height);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
        ob_start();
        imagejpeg($target, null, $quality);
        $contents = ob_get_clean();
        imagedestroy($target);
        imagedestroy($source);

        return [$contents ?: file_get_contents($sourcePath), $width, $height];
    }
}
