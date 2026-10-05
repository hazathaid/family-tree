<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyMember\ListDuplicateMemberRequest;
use App\Http\Requests\FamilyMember\MergeMemberRequest;
use App\Http\Resources\FamilyMemberResource;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Services\MemberMergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class MemberMergeController extends Controller
{
    public function __construct(private readonly MemberMergeService $merges) {}

    public function duplicates(ListDuplicateMemberRequest $request, Family $family): JsonResponse
    {
        Gate::authorize('update', $family);

        $pairs = $this->merges->detectDuplicates($family, $request->integer('limit', 25));

        $data = array_map(fn (array $pair): array => [
            'primary' => new FamilyMemberResource($pair['primary']),
            'duplicate' => new FamilyMemberResource($pair['duplicate']),
            'confidence' => $pair['confidence'],
            'reasons' => $pair['reasons'],
        ], $pairs);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $data,
        ]);
    }

    public function merge(MergeMemberRequest $request, FamilyMember $family_member): JsonResponse
    {
        Gate::authorize('merge', $family_member);

        $duplicate = FamilyMember::query()
            ->where('uuid', $request->validated('duplicate_uuid'))
            ->firstOrFail();

        $merged = $this->merges->merge($request->user(), $family_member, $duplicate);

        return response()->json([
            'success' => true,
            'message' => 'Members merged',
            'data' => new FamilyMemberResource($merged),
        ]);
    }
}
