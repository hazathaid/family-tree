<?php

namespace App\Services;

use App\DTOs\FamilyData;
use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Repositories\Contracts\FamilyRepositoryInterface;
use App\Repositories\Contracts\FamilyUserRoleRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FamilyService
{
    public function __construct(
        private readonly FamilyRepositoryInterface $families,
        private readonly FamilyUserRoleRepositoryInterface $familyRoles,
        private readonly FamilyRoleCatalogService $roleCatalog,
    ) {}

    public function create(User $creator, FamilyData $data): Family
    {
        return DB::transaction(function () use ($creator, $data): Family {
            $family = $this->families->create([
                ...$data->toAttributes(),
                'slug' => $this->uniqueSlug($data->name),
                'created_by' => $creator->id,
            ]);

            $this->familyRoles->restoreOrCreate($family, $creator, FamilyUserRole::ROLE_OWNER);
            $this->roleCatalog->syncUserGlobalRoles($creator);

            return $family->refresh();
        });
    }

    public function update(Family $family, FamilyData $data): Family
    {
        $attributes = $data->toAttributes();

        if ($family->name !== $data->name) {
            $attributes['slug'] = $this->uniqueSlug($data->name, $family->id);
        }

        $updated = $this->families->update($family, $attributes);
        Cache::forget($this->dashboardCacheKey($updated));

        return $updated;
    }

    public function delete(Family $family): void
    {
        $this->families->delete($family);
        Cache::forget($this->dashboardCacheKey($family));
    }

    public function dashboardCacheKey(Family $family): string
    {
        return 'families.'.$family->id.'.dashboard';
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $index = 2;

        while ($this->families->slugExists($slug, $ignoreId)) {
            $slug = $baseSlug.'-'.$index;
            $index++;
        }

        return $slug;
    }
}
