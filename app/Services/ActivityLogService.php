<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Event;
use App\Models\FamilyMember;
use App\Models\MemberPhoto;
use App\Models\User;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;

class ActivityLogService
{
    public function __construct(private readonly ActivityLogRepositoryInterface $activities) {}

    public function memberCreated(User $user, FamilyMember $member): ActivityLog
    {
        return $this->record($member->family_id, $user, ActivityLog::MEMBER_CREATED, $this->memberPayload($member));
    }

    public function memberUpdated(User $user, FamilyMember $member): ActivityLog
    {
        return $this->record($member->family_id, $user, ActivityLog::MEMBER_UPDATED, $this->memberPayload($member));
    }

    public function memberDeleted(User $user, FamilyMember $member): ActivityLog
    {
        return $this->record($member->family_id, $user, ActivityLog::MEMBER_DELETED, $this->memberPayload($member));
    }

    public function memberPhotoUpdated(User $user, FamilyMember $member): ActivityLog
    {
        return $this->record($member->family_id, $user, ActivityLog::MEMBER_PHOTO_UPDATED, $this->memberPayload($member));
    }

    public function articleCreated(User $user, Article $article): ActivityLog
    {
        return $this->record($article->family_id, $user, ActivityLog::ARTICLE_CREATED, ['subject_uuid' => $article->uuid, 'title' => $article->title]);
    }

    public function memberAccountInvited(User $user, FamilyMember $member): ActivityLog
    {
        return $this->record($member->family_id, $user, ActivityLog::MEMBER_ACCOUNT_INVITED, ['subject_uuid' => $member->uuid]);
    }

    public function memberAccountClaimed(User $user, FamilyMember $member): ActivityLog
    {
        return $this->record($member->family_id, $user, ActivityLog::MEMBER_ACCOUNT_CLAIMED, ['subject_uuid' => $member->uuid]);
    }

    public function treeRelativeCreated(User $user, FamilyMember $member, FamilyMember $relative, string $relation): ActivityLog
    {
        return $this->record($member->family_id, $user, ActivityLog::TREE_RELATIVE_CREATED, [
            'subject_uuid' => $relative->uuid,
            'member_uuid' => $member->uuid,
            'relation' => $relation,
            'name' => $relative->full_name,
        ]);
    }

    public function gedcomImported(User $user, FamilyMember $member, int $membersCreated, int $relationshipsCreated): ActivityLog
    {
        return $this->record($member->family_id, $user, ActivityLog::GEDCOM_IMPORTED, [
            'subject_uuid' => $member->uuid,
            'members_created' => $membersCreated,
            'relationships_created' => $relationshipsCreated,
        ]);
    }

    public function membersImported(User $user, FamilyMember $member, int $membersCreated, int $membersSkipped): ActivityLog
    {
        return $this->record($member->family_id, $user, ActivityLog::MEMBERS_IMPORTED, [
            'subject_uuid' => $member->uuid,
            'members_created' => $membersCreated,
            'members_skipped' => $membersSkipped,
        ]);
    }

    public function photoUploaded(User $user, MemberPhoto $photo): ActivityLog
    {
        return $this->record($photo->family_id, $user, ActivityLog::PHOTO_UPLOADED, ['subject_uuid' => $photo->uuid, 'caption' => $photo->caption]);
    }

    public function eventCreated(User $user, Event $event): ActivityLog
    {
        return $this->record($event->family_id, $user, ActivityLog::EVENT_CREATED, ['subject_uuid' => $event->uuid, 'title' => $event->title]);
    }

    public function record(int $familyId, ?User $user, string $type, array $payload): ActivityLog
    {
        return $this->activities->create(['family_id' => $familyId, 'user_id' => $user?->id, 'activity_type' => $type, 'payload' => $payload]);
    }

    private function memberPayload(FamilyMember $member): array
    {
        return ['subject_uuid' => $member->uuid, 'name' => $member->full_name];
    }
}
