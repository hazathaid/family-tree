<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FamilyMember;
use App\Services\MemberSpouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class MemberSpouseController extends Controller
{
    public function __construct(private readonly MemberSpouseService $spouses) {}

    public function index(FamilyMember $familyMember): JsonResponse
    {
        Gate::authorize('view', $familyMember);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $this->spouses->summaries($familyMember),
        ]);
    }
}
