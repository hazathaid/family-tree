<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Event;
use App\Models\FamilyLink;
use App\Models\FamilyMember;
use App\Models\FamilyViewGrant;
use App\Models\MemberDocument;
use App\Models\MemberPhoto;
use App\Models\MemberRelationship;
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

    public function memberMerged(User $user, FamilyMember $primary, FamilyMember $duplicate): ActivityLog
    {
        return $this->record($primary->family_id, $user, ActivityLog::MEMBER_MERGED, [
            'subject_uuid' => $primary->uuid,
            'merged_uuid' => $duplicate->uuid,
            'name' => $primary->full_name,
        ]);
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

    public function memberDocumentUploaded(User $user, FamilyMember $member, MemberDocument $document): ActivityLog
    {
        return $this->record($member->family_id, $user, ActivityLog::MEMBER_DOCUMENT_UPLOADED, [
            'subject_uuid' => $member->uuid,
            'document_uuid' => $document->uuid,
            'title' => $document->title,
        ]);
    }

    public function memberDocumentDeleted(User $user, MemberDocument $document): ActivityLog
    {
        return $this->record($document->family_id, $user, ActivityLog::MEMBER_DOCUMENT_DELETED, [
            'subject_uuid' => $document->member->uuid,
            'document_uuid' => $document->uuid,
            'title' => $document->title,
        ]);
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

    public function marriageEndCorrected(
        User $user,
        MemberRelationship $relationship,
        ?string $previousEndDate,
        ?string $previousEndReason,
    ): ActivityLog {
        return $this->record($relationship->family_id, $user, ActivityLog::MARRIAGE_END_CORRECTED, $this->marriagePayload($relationship, [
            'previous_end_date' => $previousEndDate,
            'previous_end_reason' => $previousEndReason,
        ]));
    }

    public function marriageReopened(User $user, MemberRelationship $relationship, ?string $previousEndDate, ?string $previousEndReason): ActivityLog
    {
        return $this->record($relationship->family_id, $user, ActivityLog::MARRIAGE_REOPENED, $this->marriagePayload($relationship, [
            'previous_end_date' => $previousEndDate,
            'previous_end_reason' => $previousEndReason,
        ]));
    }

    public function familyLinkRequested(User $user, FamilyLink $link): ActivityLog
    {
        return $this->record($link->family_id, $user, ActivityLog::FAMILY_LINK_REQUESTED, $this->familyLinkPayload($link));
    }

    public function familyLinkApproved(User $user, FamilyLink $link): ActivityLog
    {
        return $this->record($link->family_id, $user, ActivityLog::FAMILY_LINK_APPROVED, $this->familyLinkPayload($link));
    }

    public function familyLinkRevoked(User $user, FamilyLink $link): ActivityLog
    {
        return $this->record($link->family_id, $user, ActivityLog::FAMILY_LINK_REVOKED, $this->familyLinkPayload($link));
    }

    public function familyViewGrantCreated(User $user, FamilyViewGrant $grant): ActivityLog
    {
        return $this->record($grant->family_id, $user, ActivityLog::FAMILY_VIEW_GRANT_CREATED, $this->familyViewGrantPayload($grant));
    }

    public function familyViewGrantAccepted(User $user, FamilyViewGrant $grant): ActivityLog
    {
        return $this->record($grant->family_id, $user, ActivityLog::FAMILY_VIEW_GRANT_ACCEPTED, $this->familyViewGrantPayload($grant));
    }

    public function familyViewGrantRevoked(User $user, FamilyViewGrant $grant): ActivityLog
    {
        return $this->record($grant->family_id, $user, ActivityLog::FAMILY_VIEW_GRANT_REVOKED, $this->familyViewGrantPayload($grant));
    }

    public function familyViewAccessed(User $user, FamilyViewGrant $grant): ActivityLog
    {
        return $this->record($grant->family_id, $user, ActivityLog::FAMILY_VIEW_ACCESSED, $this->familyViewGrantPayload($grant));
    }

    public function record(int $familyId, ?User $user, string $type, array $payload): ActivityLog
    {
        return $this->activities->create(['family_id' => $familyId, 'user_id' => $user?->id, 'activity_type' => $type, 'payload' => $payload]);
    }

    private function familyViewGrantPayload(FamilyViewGrant $grant): array
    {
        return [
            'subject_uuid' => $grant->uuid,
            'anchor_member_uuid' => $grant->anchorMember?->uuid,
            'grantee_user_uuid' => $grant->grantee?->uuid,
            'expires_at' => $grant->expires_at?->toISOString(),
        ];
    }

    private function familyLinkPayload(FamilyLink $link): array
    {
        return [
            'subject_uuid' => $link->uuid,
            'member_uuid' => $link->member?->uuid,
            'linked_member_uuid' => $link->linkedMember?->uuid,
            'status' => $link->status,
        ];
    }

    private function marriagePayload(MemberRelationship $relationship, array $extra = []): array
    {
        return array_merge([
            'subject_uuid' => $relationship->uuid,
            'source_member_uuid' => $relationship->sourceMember?->uuid,
            'target_member_uuid' => $relationship->targetMember?->uuid,
            'end_date' => $relationship->end_date?->toDateString(),
            'end_reason' => $relationship->end_reason,
        ], $extra);
    }

    private function memberPayload(FamilyMember $member): array
    {
        return ['subject_uuid' => $member->uuid, 'name' => $member->full_name];
    }
}
