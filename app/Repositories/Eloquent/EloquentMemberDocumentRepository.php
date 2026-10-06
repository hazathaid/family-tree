<?php

namespace App\Repositories\Eloquent;

use App\Models\FamilyMember;
use App\Models\MemberDocument;
use App\Repositories\Contracts\MemberDocumentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentMemberDocumentRepository implements MemberDocumentRepositoryInterface
{
    public function create(array $attributes): MemberDocument
    {
        return MemberDocument::query()->create($attributes)->refresh();
    }

    public function paginateForMember(FamilyMember $member, int $perPage): LengthAwarePaginator
    {
        return MemberDocument::query()
            ->with(['family', 'member', 'uploader'])
            ->where('family_member_id', $member->id)
            ->latest()
            ->paginate($perPage);
    }

    public function delete(MemberDocument $document): void
    {
        $document->delete();
    }
}
