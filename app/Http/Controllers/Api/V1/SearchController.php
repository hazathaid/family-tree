<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\SearchCriteria;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Http\Resources\SearchResource;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $service) {}

    public function index(SearchRequest $request): JsonResponse
    {
        $input = $request->validated();
        $result = $this->service->search($request->user(), new SearchCriteria(
            $input['keyword'] ?? null,
            $input['family_uuid'] ?? null,
            isset($input['family_id']) ? (int) $input['family_id'] : null,
            $input['name'] ?? null,
            $input['city'] ?? null,
            isset($input['generation']) ? (int) $input['generation'] : null,
            $input['status'] ?? null,
            $input['root_member_uuid'] ?? null,
            isset($input['limit']) ? (int) $input['limit'] : 15,
        ));

        return response()->json(['success' => true, 'message' => 'Success', 'data' => new SearchResource($result)]);
    }
}
