<?php

namespace App\Repositories\Eloquent;

use App\DTOs\SearchCriteria;
use App\Models\Article;
use App\Models\Event;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Repositories\Contracts\SearchRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentSearchRepository implements SearchRepositoryInterface
{
    public function rootMember(User $user, string $uuid): ?FamilyMember
    {
        return FamilyMember::query()->where('uuid', $uuid)
            ->whereHas('family.userRoles', fn (Builder $query) => $query->where('user_id', $user->id))
            ->first();
    }

    public function members(User $user, SearchCriteria $criteria): Collection
    {
        return FamilyMember::query()->with(['family', 'branch'])
            ->whereHas('family.userRoles', fn (Builder $query) => $query->where('user_id', $user->id))
            ->tap(fn (Builder $query) => $this->familyScope($query, $criteria))
            ->when($criteria->keyword, fn (Builder $query, string $keyword) => $this->memberText($query, $keyword))
            ->when($criteria->name, fn (Builder $query, string $name) => $query->where(fn (Builder $nested) => $nested->where('full_name', 'like', '%'.$name.'%')->orWhere('nickname', 'like', '%'.$name.'%')))
            ->when($criteria->city, fn (Builder $query, string $city) => $query->where(fn (Builder $nested) => $nested->where('birth_place', 'like', '%'.$city.'%')->orWhere('death_place', 'like', '%'.$city.'%')))
            ->when($criteria->status, fn (Builder $query, string $status) => $query->where('is_alive', $status === 'alive'))
            ->orderBy('full_name')->limit($criteria->generation === null ? $criteria->limit : 100000)->get();
    }

    public function articles(User $user, SearchCriteria $criteria): Collection
    {
        if (! $criteria->keyword) {
            return collect();
        }

        return Article::query()->with(['family', 'category', 'author'])->withCount(['likes', 'comments'])
            ->whereHas('family.userRoles', fn (Builder $query) => $query->where('user_id', $user->id))
            ->where(fn (Builder $query) => $query->where('status', Article::STATUS_PUBLISHED)
                ->orWhere('author_id', $user->id)
                ->orWhereHas('family.userRoles', fn (Builder $roles) => $roles->where('user_id', $user->id)->whereIn('role', [FamilyUserRole::ROLE_OWNER, FamilyUserRole::ROLE_ADMIN])))
            ->tap(fn (Builder $query) => $this->familyScope($query, $criteria))
            ->where(fn (Builder $query) => $query->where('title', 'like', '%'.$criteria->keyword.'%')->orWhere('content', 'like', '%'.$criteria->keyword.'%'))
            ->latest()->limit($criteria->limit)->get();
    }

    public function events(User $user, SearchCriteria $criteria): Collection
    {
        if (! $criteria->keyword) {
            return collect();
        }

        return Event::query()->with(['family', 'organizer'])->withCount(['attendees', 'attendees as yes_count' => fn (Builder $query) => $query->where('status', 'yes'), 'attendees as maybe_count' => fn (Builder $query) => $query->where('status', 'maybe')])
            ->whereHas('family.userRoles', fn (Builder $query) => $query->where('user_id', $user->id))
            ->tap(fn (Builder $query) => $this->familyScope($query, $criteria))
            ->where(fn (Builder $query) => $query->where('title', 'like', '%'.$criteria->keyword.'%')->orWhere('description', 'like', '%'.$criteria->keyword.'%')->orWhere('location', 'like', '%'.$criteria->keyword.'%'))
            ->orderBy('event_date')->limit($criteria->limit)->get();
    }

    private function familyScope(Builder $query, SearchCriteria $criteria): void
    {
        $query->when($criteria->familyUuid, fn (Builder $nested, string $uuid) => $nested->whereHas('family', fn (Builder $family) => $family->where('uuid', $uuid)))
            ->when($criteria->familyId, fn (Builder $nested, int $id) => $nested->where('family_id', $id));
    }

    private function memberText(Builder $query, string $keyword): void
    {
        $query->where(fn (Builder $nested) => $nested->where('full_name', 'like', '%'.$keyword.'%')->orWhere('nickname', 'like', '%'.$keyword.'%')->orWhere('birth_place', 'like', '%'.$keyword.'%'));
    }
}
