<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyMember\ImportMembersRequest;
use App\Models\Family;
use App\Services\MemberBulkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class MemberBulkController extends Controller
{
    public function __construct(private readonly MemberBulkService $bulk) {}

    public function import(ImportMembersRequest $request, Family $family): JsonResponse
    {
        Gate::authorize('update', $family);

        $summary = $this->bulk->import($request->user(), $family, $request->file('file')?->get() ?? '');

        return response()->json([
            'success' => true,
            'message' => 'Members imported',
            'data' => $summary,
        ]);
    }

    public function export(Family $family): Response
    {
        Gate::authorize('view', $family);

        return response($this->bulk->export($family), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($family->name).'-members.csv"',
        ]);
    }
}
