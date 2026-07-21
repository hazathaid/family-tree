<?php

namespace App\Services;

use App\Models\Family;
use App\Models\User;
use App\Repositories\Contracts\GamificationRepositoryInterface;
use Illuminate\Support\Collection;

class GamificationService
{
    public const ACTION_ADD_MEMBER = 'add_member';

    public const ACTION_UPLOAD_PHOTO = 'upload_photo';

    public const ACTION_WRITE_ARTICLE = 'write_article';

    private const POINTS = [
        self::ACTION_ADD_MEMBER => 10,
        self::ACTION_UPLOAD_PHOTO => 5,
        self::ACTION_WRITE_ARTICLE => 15,
    ];

    private const BADGES = [
        'penjaga_sejarah' => ['Penjaga Sejarah', 'Mengunggah 10 foto keluarga.', self::ACTION_UPLOAD_PHOTO, 10],
        'penulis_keluarga' => ['Penulis Keluarga', 'Menulis 5 artikel keluarga.', self::ACTION_WRITE_ARTICLE, 5],
        'ahli_silsilah' => ['Ahli Silsilah', 'Menambahkan 25 anggota keluarga.', self::ACTION_ADD_MEMBER, 25],
    ];

    public function __construct(private readonly GamificationRepositoryInterface $gamification) {}

    public function award(Family $family, User $user, string $action, string $sourceType, int $sourceId): void
    {
        $this->gamification->record($family, $user, $action, self::POINTS[$action], $sourceType, $sourceId);
        $this->evaluateBadges($family, $user);
    }

    public function profile(Family $family, User $user): array
    {
        return [
            'points' => $this->gamification->totalPoints($family, $user),
            'badges' => $this->gamification->badges($family, $user),
        ];
    }

    public function userLeaderboard(Family $family, int $limit): Collection
    {
        return $this->rank($this->gamification->userLeaderboard($family, $limit));
    }

    public function familyLeaderboard(int $limit): Collection
    {
        return $this->rank($this->gamification->familyLeaderboard($limit));
    }

    private function evaluateBadges(Family $family, User $user): void
    {
        foreach (self::BADGES as $code => [$name, $description, $action, $threshold]) {
            if ($this->gamification->actionCount($family, $user, $action) >= $threshold) {
                $badge = $this->gamification->findOrCreateBadge($code, $name, $description);
                $this->gamification->award($family, $user, $badge);
            }
        }

        if ($this->gamification->totalPoints($family, $user) >= 100) {
            $badge = $this->gamification->findOrCreateBadge('kontributor_aktif', 'Kontributor Aktif', 'Mengumpulkan 100 poin kontribusi.');
            $this->gamification->award($family, $user, $badge);
        }
    }

    private function rank(Collection $rows): Collection
    {
        return $rows->values()->map(function (object $row, int $index): object {
            $row->rank = $index + 1;
            $row->points = (int) $row->points;

            return $row;
        });
    }
}
