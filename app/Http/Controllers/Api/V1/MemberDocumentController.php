<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyMember\ListMemberDocumentRequest;
use App\Http\Requests\FamilyMember\StoreMemberDocumentRequest;
use App\Http\Resources\MemberDocumentResource;
use App\Models\FamilyMember;
use App\Models\MemberDocument;
use App\Repositories\Contracts\MemberDocumentRepositoryInterface;
use App\Services\MemberDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MemberDocumentController extends Controller
{
    public function __construct(
        private readonly MemberDocumentRepositoryInterface $documents,
        private readonly MemberDocumentService $service,
    ) {}

    public function index(ListMemberDocumentRequest $request, FamilyMember $family_member): JsonResponse
    {
        Gate::authorize('viewAny', [MemberDocument::class, $family_member]);

        $page = $this->documents->paginateForMember($family_member, min($request->integer('limit', 15), 100));

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => MemberDocumentResource::collection($page),
        ]);
    }

    public function store(StoreMemberDocumentRequest $request, FamilyMember $family_member): JsonResponse
    {
        Gate::authorize('create', [MemberDocument::class, $family_member]);

        $document = $this->service->store($request->user(), $family_member, $request->validated(), $request->file('file'));

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded',
            'data' => new MemberDocumentResource($document),
        ], 201);
    }

    public function show(MemberDocument $member_document): JsonResponse
    {
        $member_document->load(['family', 'member', 'uploader']);
        Gate::authorize('view', $member_document);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => new MemberDocumentResource($member_document),
        ]);
    }

    public function download(MemberDocument $member_document): Response
    {
        $member_document->load('member.family');
        Gate::authorize('view', $member_document);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($member_document->path), 404);

        return response($disk->get($member_document->path), 200, [
            'Content-Type' => $member_document->mime_type,
            'Content-Disposition' => 'attachment; filename="'.addslashes($member_document->original_name).'"',
        ]);
    }

    public function destroy(Request $request, MemberDocument $member_document): JsonResponse
    {
        $member_document->load('member');
        Gate::authorize('delete', $member_document);

        $this->service->delete($request->user(), $member_document);

        return response()->json(['success' => true, 'message' => 'Document deleted', 'data' => null]);
    }
}
