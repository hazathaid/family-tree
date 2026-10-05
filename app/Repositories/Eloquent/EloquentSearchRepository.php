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
        $query = $this->memberQuery($user, $criteria);

        $query->offset(($criteria->page - 1) * $criteria->limit)->limit($criteria->limit);

        return $query->get();
    }

    /**
     * @param  array<int, int>  $memberIds
     */
    public function membersForIds(User $user, SearchCriteria $criteria, array $memberIds): Collection
    {
        if ($memberIds === []) {
            return collect();
        }

        $query = $this->memberQuery($user, $criteria)
            ->whereIn('id', $memberIds)
            ->offset(($criteria->page - 1) * $criteria->limit)
            ->limit($criteria->limit);

        return $query->get();
    }

    private function memberQuery(User $user, SearchCriteria $criteria): Builder
    {
        $query = FamilyMember::query()->with(['family', 'branch'])
            ->whereHas('family.userRoles', fn (Builder $nested) => $nested->where('user_id', $user->id));
        $query = $this->familyScope($query, $criteria);

        $query = $query
            ->when($criteria->keyword, fn (Builder $nested, string $keyword) => $this->memberText($nested, $keyword))
            ->when($criteria->name, fn (Builder $nested, string $name) => $nested->where(fn (Builder $inner) => $inner->where('full_name', 'like', '%'.$name.'%')->orWhere('nickname', 'like', '%'.$name.'%')))
            ->when($criteria->city, fn (Builder $nested, string $city) => $nested->where(fn (Builder $inner) => $inner->where('birth_place', 'like', '%'.$city.'%')->orWhere('death_place', 'like', '%'.$city.'%')))
            ->when($criteria->status, fn (Builder $nested, string $status) => $nested->where('is_alive', $status === 'alive'));

        $query->orderBy('full_name');

        return $query;
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
            ->latest()->offset(($criteria->page - 1) * $criteria->limit)->limit($criteria->limit)->get();
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
            ->orderBy('event_date')->offset(($criteria->page - 1) * $criteria->limit)->limit($criteria->limit)->get();
    }

    private function familyScope(Builder $query, SearchCriteria $criteria): Builder
    {
        return $query->when($criteria->familyUuid, fn (Builder $nested, string $uuid) => $nested->whereHas('family', fn (Builder $family) => $family->where('uuid', $uuid)))
            ->when($criteria->familyId, fn (Builder $nested, int $id) => $nested->where('family_id', $id));
    }

    private function memberText(Builder $query, string $keyword): Builder
    {
        return $query->where(fn (Builder $nested) => $nested->where('full_name', 'like', '%'.$keyword.'%')->orWhere('nickname', 'like', '%'.$keyword.'%')->orWhere('birth_place', 'like', '%'.$keyword.'%'));
    }
}
