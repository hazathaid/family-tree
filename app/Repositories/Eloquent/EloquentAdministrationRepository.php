<?php

namespace App\Repositories\Eloquent;

use App\Models\Article;
use App\Models\Event;
use App\Models\Family;
use App\Models\MemberPhoto;
use App\Repositories\Contracts\AdministrationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class EloquentAdministrationRepository implements AdministrationRepositoryInterface
{
    private const CONTENT_MODELS = [
        'article' => Article::class,
        'photo' => MemberPhoto::class,
        'event' => Event::class,
    ];

    public function paginateFamilies(int $perPage): LengthAwarePaginator
    {
        return Family::query()
            ->with('creator:id,uuid,name,email')
            ->withCount(['members', 'articles', 'photos', 'events'])
            ->latest()
            ->paginate($perPage);
    }

    public function findFamilyContent(Family $family, string $type, string $uuid): ?Model
    {
        $model = self::CONTENT_MODELS[$type];

        return $model::query()->where('family_id', $family->id)->where('uuid', $uuid)->first();
    }

    public function deleteContent(Model $content): void
    {
        $content->delete();
    }
}
