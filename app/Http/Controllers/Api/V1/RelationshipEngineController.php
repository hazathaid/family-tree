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
        $source = FamilyMember::query()->where('uuid', $request->string('source_member_uuid'))->firstOrFail();
        $target = FamilyMember::query()->where('uuid', $request->string('target_member_uuid'))->firstOrFail();

        Gate::authorize('view', $source);
        Gate::authorize('view', $target);

        $result = $this->resolver->resolve($source, $target);
        $result['path'] = array_map(static fn (array $step): array => [
            'from_member_uuid' => $step['from_member_uuid'] ?? null,
            'from_member_name' => $step['from_member_name'] ?? null,
            'to_member_uuid' => $step['to_member_uuid'] ?? null,
            'to_member_name' => $step['to_member_name'] ?? null,
            'relationship' => $step['relationship'],
            'relationship_type' => $step['relationship_type'],
        ], $result['path']);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $result,
        ]);
    }
}
