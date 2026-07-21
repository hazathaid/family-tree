<?php

namespace App\Repositories\Eloquent;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\FamilyDashboardRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentFamilyDashboardRepository implements FamilyDashboardRepositoryInterface
{
    public function totalMembers(Family $family): int
    {
        return $this->countTable('family_members', $family);
    }

    public function livingMembers(Family $family): int
    {
        return $this->countTable('family_members', $family, ['is_alive' => true]);
    }

    public function deceasedMembers(Family $family): int
    {
        return $this->countTable('family_members', $family, ['is_alive' => false]);
    }

    public function totalArticles(Family $family): int
    {
        return $this->countTable('articles', $family);
    }

    public function totalPhotos(Family $family): int
    {
        return $this->countTable('member_photos', $family);
    }

    public function totalEvents(Family $family): int
    {
        return $this->countTable('events', $family);
    }

    public function recentActivity(Family $family, int $limit): Collection
    {
        return ActivityLog::query()
            ->with('user:id,name')
            ->where('family_id', $family->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function upcomingBirthdays(Family $family, int $days, int $limit): Collection
    {
        $members = collect();

        for ($offset = 0; $offset <= $days && $members->count() < $limit; $offset++) {
            $date = Date::today()->addDays($offset);
            $remaining = $limit - $members->count();
            /** @var Collection<int, FamilyMember> $matches */
            $matches = FamilyMember::query()
                ->where('family_id', $family->id)
                ->whereNotNull('birth_date')
                ->whereMonth('birth_date', $date->month)
                ->whereDay('birth_date', $date->day)
                ->orderBy('birth_date')
                ->limit($remaining)
                ->get();

            $members->push(...$matches->map(function (FamilyMember $member) use ($date): FamilyMember {
                $member->setAttribute('next_birthday', $date->toDateString());

                return $member;
            }));
        }

        return $members;
    }

    public function upcomingEvents(Family $family, int $limit): Collection
    {
        return Event::query()
            ->where('family_id', $family->id)
            ->where('event_date', '>=', now())
            ->orderBy('event_date')
            ->limit($limit)
            ->get();
    }

    public function recentNotifications(Family $family, User $user, int $limit): Collection
    {
        return $this->familyNotifications($family, $user)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function unreadNotificationsCount(Family $family, User $user): int
    {
        return $this->familyNotifications($family, $user)
            ->where('is_read', false)
            ->count();
    }

    public function recentlyAddedMembers(Family $family, int $limit): Collection
    {
        return FamilyMember::query()
            ->where('family_id', $family->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function oldestLivingMember(Family $family): ?FamilyMember
    {
        /** @var FamilyMember|null $member */
        $member = FamilyMember::query()
            ->where('family_id', $family->id)
            ->where('is_alive', true)
            ->whereNotNull('birth_date')
            ->oldest('birth_date')
            ->first();

        return $member;
    }

    public function youngestLivingMember(Family $family): ?FamilyMember
    {
        /** @var FamilyMember|null $member */
        $member = FamilyMember::query()
            ->where('family_id', $family->id)
            ->where('is_alive', true)
            ->whereNotNull('birth_date')
            ->latest('birth_date')
            ->first();

        return $member;
    }

    private function familyNotifications(Family $family, User $user): Builder
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where(function (Builder $query) use ($family): void {
                $query->whereHas('event', fn (Builder $event): Builder => $event->where('family_id', $family->id))
                    ->orWhere('data->family_uuid', $family->uuid)
                    ->orWhere('data->family_id', $family->id);
            });
    }

    private function countTable(string $table, Family $family, array $conditions = []): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);

        if (Schema::hasColumn($table, 'family_id')) {
            $query->where('family_id', $family->id);
        } elseif ($table === 'member_photos' && Schema::hasTable('family_members')) {
            $query->join('family_members', 'member_photos.member_id', '=', 'family_members.id')
                ->where('family_members.family_id', $family->id);
        } else {
            return 0;
        }

        foreach ($conditions as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $query->where($column, $value);
            }
        }

        return $query->count();
    }
}
