<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\FamilyMember;
use App\Models\MemberPhoto;
use App\Models\User;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;

class ActivityLogService
{
    public function __construct(private readonly ActivityLogRepositoryInterface $activities) {}

    public function memberCreated(User $user, FamilyMember $member): ActivityLog
    {
        return $this->record($member->family_id, $user, ActivityLog::MEMBER_CREATED, ['subject_uuid' => $member->uuid, 'name' => $member->full_name]);
    }

    public function articleCreated(User $user, Article $article): ActivityLog
    {
        return $this->record($article->family_id, $user, ActivityLog::ARTICLE_CREATED, ['subject_uuid' => $article->uuid, 'title' => $article->title]);
    }

    public function photoUploaded(User $user, MemberPhoto $photo): ActivityLog
    {
        return $this->record($photo->family_id, $user, ActivityLog::PHOTO_UPLOADED, ['subject_uuid' => $photo->uuid, 'caption' => $photo->caption]);
    }

    public function record(int $familyId, ?User $user, string $type, array $payload): ActivityLog
    {
        return $this->activities->create(['family_id' => $familyId, 'user_id' => $user?->id, 'activity_type' => $type, 'payload' => $payload]);
    }
}
