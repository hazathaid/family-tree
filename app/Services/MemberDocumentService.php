<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\MemberDocument;
use App\Models\User;
use App\Repositories\Contracts\MemberDocumentRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MemberDocumentService
{
    public function __construct(
        private readonly MemberDocumentRepositoryInterface $documents,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function store(User $user, FamilyMember $member, array $data, UploadedFile $file): MemberDocument
    {
        $member->loadMissing('family');
        $directory = 'member-documents/'.$member->family->uuid.'/'.$member->uuid;
        $path = $file->store($directory, 'public');

        $document = $this->documents->create([
            'family_id' => $member->family_id,
            'family_member_id' => $member->id,
            'uploaded_by' => $user->id,
            'title' => $data['title'],
            'category' => $data['category'] ?? null,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: ($file->getMimeType() ?: 'application/octet-stream'),
            'size' => (int) $file->getSize(),
            'document_date' => $data['document_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->activityLog->memberDocumentUploaded($user, $member, $document);

        return $document->load(['family', 'member', 'uploader']);
    }

    public function delete(User $user, MemberDocument $document): void
    {
        $document->loadMissing('member');
        Storage::disk('public')->delete($document->path);
        $this->activityLog->memberDocumentDeleted($user, $document);
        $this->documents->delete($document);
    }
}
