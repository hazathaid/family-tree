<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Services\GedcomExportService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FamilyExportController extends Controller
{
    public function __construct(private readonly GedcomExportService $gedcom) {}

    public function gedcom(Family $family): Response
    {
        Gate::authorize('view', $family);

        return response($this->gedcom->export($family), 200, [
            'Content-Type' => 'text/x-gedcom; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->gedcom->filename($family).'"',
        ]);
    }
}
