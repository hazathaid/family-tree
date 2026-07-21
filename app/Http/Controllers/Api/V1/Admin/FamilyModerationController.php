<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\AdminIndexRequest;
use App\Http\Requests\Administration\RemoveFamilyContentRequest;
use App\Http\Resources\AdminFamilyResource;
use App\Models\Family;
use App\Services\AdministrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class FamilyModerationController extends Controller
{
    public function __construct(private readonly AdministrationService $administration) {}

    public function index(AdminIndexRequest $request): JsonResponse
    {
        Gate::authorize('administer');

        return $this->success(AdminFamilyResource::collection($this->administration->families($request->integer('per_page', 15))));
    }

    public function show(Family $family): JsonResponse
    {
        Gate::authorize('administer');
        $family->load('creator:id,uuid,name,email')->loadCount(['members', 'articles', 'photos', 'events']);

        return $this->success(new AdminFamilyResource($family));
    }

    public function destroyContent(RemoveFamilyContentRequest $request, Family $family): JsonResponse
    {
        Gate::authorize('administer');
        $this->administration->removeContent(
            $request->user(),
            $family,
            $request->validated('content_type'),
            $request->validated('content_uuid'),
        );

        return $this->success(null, 'Content removed');
    }

    private function success(mixed $data, string $message = 'Success'): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data]);
    }
}
