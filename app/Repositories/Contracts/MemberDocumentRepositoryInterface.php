<?php

namespace App\Repositories\Contracts;

use App\Models\FamilyMember;
use App\Models\MemberDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MemberDocumentRepositoryInterface
{
    public function create(array $attributes): MemberDocument;

    public function paginateForMember(FamilyMember $member, int $perPage): LengthAwarePaginator;

    public function delete(MemberDocument $document): void;
}
