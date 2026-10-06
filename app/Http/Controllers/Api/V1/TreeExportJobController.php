<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tree\RequestTreeExportRequest;
use App\Http\Resources\TreeExportResource;
use App\Jobs\GenerateTreeExport;
use App\Models\FamilyMember;
use App\Models\TreeExport;
use App\Services\TreeExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TreeExportJobController extends Controller
{
    public function __construct(private readonly TreeExportService $exports) {}

    public function store(RequestTreeExportRequest $request): JsonResponse
    {
        $root = FamilyMember::query()->where('uuid', $request->string('member_uuid'))->firstOrFail();
        Gate::authorize('view', $root);

        $export = $this->exports->request(
            $request->user(),
            $root,
            $request->string('mode', 'full')->toString(),
            $request->integer('depth', 5),
            $request->string('layout', 'vertical')->toString(),
            $request->string('format')->toString(),
            $request->string('paper_size', 'A4')->toString(),
        );

        GenerateTreeExport::dispatch($export->id)->afterCommit();

        return response()->json([
            'success' => true,
            'message' => 'Tree export queued',
            'data' => new TreeExportResource($export),
        ], 202);
    }

    public function show(TreeExport $tree_export): JsonResponse
    {
        $this->authorizeExport($tree_export);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => new TreeExportResource($tree_export),
        ]);
    }

    public function download(TreeExport $tree_export): Response
    {
        $this->authorizeExport($tree_export);

        $path = $tree_export->path;
        abort_unless(
            $tree_export->isCompleted() && is_string($path) && Storage::disk('local')->exists($path),
            404,
        );

        $content = Storage::disk('local')->get($path);
        $mime = $tree_export->format === TreeExport::FORMAT_PNG ? 'image/png' : 'application/pdf';

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="family-tree.'.$tree_export->format.'"',
        ]);
    }

    private function authorizeExport(TreeExport $export): void
    {
        $member = $export->member;

        abort_if(! $member instanceof FamilyMember, 404);

        Gate::authorize('view', $member);
    }
}
