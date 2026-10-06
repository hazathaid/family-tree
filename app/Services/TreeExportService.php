<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\TreeExport;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TreeExportService
{
    public function __construct(
        private readonly TreePresentationService $trees,
        private readonly TreePngExportService $png,
        private readonly TreePdfExportService $pdf,
    ) {}

    public function request(
        User $user,
        FamilyMember $root,
        string $mode,
        int $depth,
        string $layout,
        string $format,
        string $paperSize = 'A4',
    ): TreeExport {
        return TreeExport::query()->create([
            'uuid' => (string) Str::uuid(),
            'family_id' => $root->family_id,
            'user_id' => $user->id,
            'root_member_id' => $root->id,
            'mode' => $mode,
            'depth' => $depth,
            'layout' => $layout,
            'format' => $format,
            'paper_size' => $paperSize,
            'status' => TreeExport::STATUS_PENDING,
        ]);
    }

    public function run(TreeExport $export): void
    {
        $export->update(['status' => TreeExport::STATUS_PROCESSING, 'error' => null]);

        try {
            $root = $export->member()->first();

            if (! $root instanceof FamilyMember) {
                throw new \RuntimeException('Tree root member is no longer available.');
            }

            $tree = $this->trees->present($root, $export->mode, $export->depth, $export->layout);
            $content = $export->format === TreeExport::FORMAT_PNG
                ? $this->png->export($tree, $export->paper_size)
                : $this->pdf->export($tree, $export->paper_size);

            $path = 'tree-exports/'.$export->family()->value('uuid').'/'.$export->uuid.'.'.$export->format;
            Storage::disk('local')->put($path, $content);

            $export->update([
                'status' => TreeExport::STATUS_COMPLETED,
                'path' => $path,
                'completed_at' => now(),
                'expires_at' => now()->addDays(7),
            ]);
        } catch (Throwable $exception) {
            $export->update([
                'status' => TreeExport::STATUS_FAILED,
                'error' => 'Tree export failed.',
            ]);

            throw $exception;
        }
    }

    public function delete(TreeExport $export): void
    {
        if (is_string($export->path) && $export->path !== '') {
            Storage::disk('local')->delete($export->path);
        }

        $export->delete();
    }

    public function prune(): int
    {
        $pruned = 0;

        $ids = TreeExport::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->pluck('id');

        foreach ($ids as $id) {
            $export = TreeExport::query()->find($id);

            if ($export instanceof TreeExport) {
                $this->delete($export);
                $pruned++;
            }
        }

        return $pruned;
    }
}
