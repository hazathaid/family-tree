<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tree\GenerateTreeRequest;
use App\Http\Resources\TreeResource;
use App\Models\FamilyMember;
use App\Services\TreePresentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class FamilyTreeController extends Controller
{
    public function __construct(private readonly TreePresentationService $trees) {}

    public function generate(GenerateTreeRequest $request): JsonResponse
    {
        $root = FamilyMember::query()->where('uuid', $request->string('member_uuid'))->firstOrFail();
        Gate::authorize('view', $root);
        $data = $this->trees->present($root, $request->string('mode', 'full')->toString(), $request->integer('depth', 5), $request->string('layout', 'vertical')->toString(), $request->user());

        return response()->json(['success' => true, 'message' => 'Tree generated successfully', 'data' => (new TreeResource($data))->resolve($request)]);
    }
}
