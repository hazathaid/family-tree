<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Relationship\ResolveRelationshipRequest;
use App\Models\FamilyMember;
use App\Services\RelationshipResolverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RelationshipEngineController extends Controller
{
    public function __construct(
        private readonly RelationshipResolverService $resolver,
    ) {}

    public function show(ResolveRelationshipRequest $request): JsonResponse
    {
        $source = FamilyMember::query()->findOrFail($request->integer('source_member_id'));
        $target = FamilyMember::query()->findOrFail($request->integer('target_member_id'));

        Gate::authorize('view', $source);
        Gate::authorize('view', $target);

        $result = $this->resolver->resolve($source, $target);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $result,
        ]);
    }
}
