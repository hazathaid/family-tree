<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\FamilyMember;
use App\Models\User;
use App\Repositories\Contracts\FamilyBranchRepositoryInterface;
use App\Repositories\Contracts\FamilyMemberRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FamilyMemberService
{
    public function __construct(
        private readonly FamilyMemberRepositoryInterface $members,
        private readonly FamilyBranchRepositoryInterface $branches,
    ) {}

    public function create(User $user, Family $family, array $data): FamilyMember
    {
        return $this->members->create([
            ...$this->memberAttributes($family, $data),
            'family_id' => $family->id,
            'created_by' => $user->id,
        ]);
    }

    public function update(FamilyMember $member, array $data): FamilyMember
    {
        return $this->members->update($member, $this->memberAttributes($member->family, $data));
    }

    public function delete(FamilyMember $member): void
    {
        $this->members->delete($member);
    }

    public function uploadPhoto(FamilyMember $member, UploadedFile $photo): FamilyMember
    {
        $disk = Storage::disk('public');

        if ($member->profile_photo) {
            $disk->delete($member->profile_photo);
        }

        if ($member->profile_photo_thumbnail) {
            $disk->delete($member->profile_photo_thumbnail);
        }

        $directory = 'family-members/'.$member->uuid.'/photos';
        $path = $photo->store($directory, 'public');
        $thumbnailPath = $directory.'/thumb_'.$photo->hashName();

        $this->createThumbnail($photo, $thumbnailPath);

        return $this->members->update($member, [
            'profile_photo' => $path,
            'profile_photo_thumbnail' => $thumbnailPath,
        ]);
    }

    private function memberAttributes(Family $family, array $data): array
    {
        return [
            'family_branch_id' => $this->resolveBranchId($family, $data['family_branch_uuid'] ?? null),
            'full_name' => $data['full_name'],
            'nickname' => $data['nickname'] ?? null,
            'gender' => $data['gender'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'birth_place' => $data['birth_place'] ?? null,
            'is_alive' => $data['is_alive'] ?? true,
            'death_date' => $data['death_date'] ?? null,
            'death_place' => $data['death_place'] ?? null,
            'biography' => $data['biography'] ?? null,
        ];
    }

    private function resolveBranchId(Family $family, ?string $branchUuid): ?int
    {
        if ($branchUuid === null) {
            return null;
        }

        $branch = $this->branches->findByUuid($branchUuid);

        if (! $branch instanceof FamilyBranch || $branch->family_id !== $family->id) {
            throw ValidationException::withMessages([
                'family_branch_uuid' => ['The selected branch does not belong to this family.'],
            ]);
        }

        return $branch->id;
    }

    private function createThumbnail(UploadedFile $photo, string $thumbnailPath): void
    {
        $disk = Storage::disk('public');
        $sourcePath = $photo->getRealPath();
        $mimeType = $photo->getMimeType();

        if ($sourcePath === false || ! function_exists('imagecreatetruecolor')) {
            $disk->put($thumbnailPath, file_get_contents($photo->getRealPath()));

            return;
        }

        $source = match ($mimeType) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? imagecreatefromjpeg($sourcePath) : false,
            'image/png' => function_exists('imagecreatefrompng') ? imagecreatefrompng($sourcePath) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if ($source === false) {
            $disk->put($thumbnailPath, file_get_contents($photo->getRealPath()));

            return;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $targetSize = 300;
        $ratio = min($targetSize / max($width, 1), $targetSize / max($height, 1), 1);
        $targetWidth = max((int) round($width * $ratio), 1);
        $targetHeight = max((int) round($height * $ratio), 1);
        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        $written = match ($mimeType) {
            'image/png' => imagepng($thumbnail),
            'image/webp' => function_exists('imagewebp') ? imagewebp($thumbnail) : imagejpeg($thumbnail, null, 85),
            default => imagejpeg($thumbnail, null, 85),
        };
        $contents = ob_get_clean();

        imagedestroy($thumbnail);
        imagedestroy($source);

        if ($written === false || $contents === false) {
            $disk->put($thumbnailPath, file_get_contents($photo->getRealPath()));

            return;
        }

        $disk->put($thumbnailPath, $contents);
    }
}
