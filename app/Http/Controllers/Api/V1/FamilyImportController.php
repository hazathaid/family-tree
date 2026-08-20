<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\ImportGedcomRequest;
use App\Models\Family;
use App\Services\GedcomImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class FamilyImportController extends Controller
{
    public function __construct(private readonly GedcomImportService $gedcomImport) {}

    public function gedcom(ImportGedcomRequest $request, Family $family): JsonResponse
    {
        Gate::authorize('update', $family);

        $content = $request->file('file')?->get() ?? '';
        $summary = $this->gedcomImport->import($request->user(), $family, $content);

        return response()->json([
            'success' => true,
            'message' => 'GEDCOM imported',
            'data' => $summary,
        ]);
    }
}
