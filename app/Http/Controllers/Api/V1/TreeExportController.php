<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tree\ExportTreeRequest;
use App\Models\FamilyMember;
use App\Services\FamilyTreeService;
use App\Services\TreeLayoutService;
use App\Services\TreePdfExportService;
use App\Services\TreePngExportService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TreeExportController extends Controller
{
    public function __construct(private readonly FamilyTreeService $trees, private readonly TreeLayoutService $layouts, private readonly TreePngExportService $png, private readonly TreePdfExportService $pdf) {}

    public function png(ExportTreeRequest $request): Response
    {
        return $this->download($request, 'png');
    }

    public function pdf(ExportTreeRequest $request): Response
    {
        return $this->download($request, 'pdf');
    }

    private function download(ExportTreeRequest $request, string $format): Response
    {
        $root = FamilyMember::query()->where('uuid', $request->string('member_uuid'))->firstOrFail();
        Gate::authorize('view', $root);
        $tree = $this->layouts->layout($this->trees->generate($root, $request->string('mode', 'full')->toString(), $request->integer('depth', 5)), $request->string('layout', 'vertical')->toString());
        $paper = $request->string('paper_size', 'A4')->toString();
        $content = $format === 'png' ? $this->png->export($tree, $paper) : $this->pdf->export($tree, $paper);

        return response($content, 200, ['Content-Type' => $format === 'png' ? 'image/png' : 'application/pdf', 'Content-Disposition' => 'attachment; filename="family-tree.'.$format.'"']);
    }
}
