<?php

namespace App\Repositories\Eloquent;

use App\Models\Family;
use App\Repositories\Contracts\FamilyDashboardRepositoryInterface;
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
