<?php

namespace App\Repositories\Eloquent;

use App\DTOs\ReportCriteria;
use App\Models\Article;
use App\Models\Family;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentReportRepository implements ReportRepositoryInterface
{
    public function memberStatistics(Family $family): array
    {
        $members = DB::table('family_members')
            ->where('family_id', $family->id)
            ->whereNull('deleted_at');

        return [
            'total_members' => (clone $members)->count(),
            'alive_members' => (clone $members)->where('is_alive', true)->count(),
            'deceased_members' => (clone $members)->where('is_alive', false)->count(),
            'root_member_id' => (clone $members)->orderBy('id')->value('id'),
        ];
    }

    public function activityReport(Family $family, ReportCriteria $criteria): array
    {
        $activities = DB::table('activity_logs')
            ->where('family_id', $family->id)
            ->whereBetween('created_at', [$criteria->from, $criteria->to]);
        $photos = DB::table('member_photos')
            ->where('family_id', $family->id)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$criteria->from, $criteria->to]);
        $articles = DB::table('articles')
            ->where('family_id', $family->id)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$criteria->from, $criteria->to]);

        return [
            'active_users' => (clone $activities)->whereNotNull('user_id')->distinct()->count('user_id'),
            'uploads' => [
                'total' => (clone $photos)->count(),
                'contributors' => (clone $photos)->distinct()->count('uploaded_by'),
            ],
            'articles' => [
                'total' => (clone $articles)->count(),
                'published' => (clone $articles)->where('status', Article::STATUS_PUBLISHED)->count(),
                'contributors' => (clone $articles)->distinct()->count('author_id'),
            ],
        ];
    }

    public function webInsights(Family $family, ReportCriteria $criteria): array
    {
        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? 'substr(created_at, 1, 7)'
            : "DATE_FORMAT(created_at, '%Y-%m')";
        $cities = DB::table('family_members')->where('family_id', $family->id)->whereNull('deleted_at')
            ->selectRaw("COALESCE(NULLIF(birth_place, ''), 'Tidak diketahui') AS label, COUNT(*) AS total")
            ->groupBy('label')->orderByDesc('total')->limit(10)->get();
        $growth = DB::table('family_members')->where('family_id', $family->id)->whereNull('deleted_at')
            ->whereBetween('created_at', [$criteria->from, $criteria->to])
            ->selectRaw("{$monthExpression} AS label, COUNT(*) AS total")
            ->groupBy('label')->orderBy('label')->get();
        $activity = DB::table('activity_logs')->where('family_id', $family->id)
            ->whereBetween('created_at', [$criteria->from, $criteria->to])
            ->selectRaw('DATE(created_at) AS label, COUNT(*) AS total')
            ->groupBy('label')->orderBy('label')->get();

        return compact('cities', 'growth', 'activity');
    }
}
