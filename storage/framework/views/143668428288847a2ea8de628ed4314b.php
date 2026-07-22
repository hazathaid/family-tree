<?php if (isset($component)) { $__componentOriginal5863877a5171c196453bfa0bd807e410 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5863877a5171c196453bfa0bd807e410 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.app','data' => ['title' => 'Pohon Keluarga']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Pohon Keluarga']); ?>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-primary fw-semibold mb-1"><?php echo e($family->name); ?></p>
            <h1 class="h3 mb-1">Pohon Keluarga</h1>
            <p class="text-body-secondary mb-0">Jelajahi hubungan keluarga dari anggota yang Anda pilih.</p>
        </div>
        <?php if($tree): ?>
            <div class="d-flex gap-2" aria-label="Statistik pohon">
                <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['variant' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary']); ?><?php echo e($tree['statistics']['members']); ?> anggota <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['variant' => 'secondary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary']); ?><?php echo e($tree['statistics']['generations']); ?> generasi <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if(!$root): ?>
        <?php if (isset($component)) { $__componentOriginal074a021b9d42f490272b5eefda63257c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal074a021b9d42f490272b5eefda63257c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty-state','data' => ['title' => 'Belum ada anggota keluarga','description' => 'Tambahkan anggota pertama untuk mulai membangun pohon keluarga.','actionLabel' => 'Tambah anggota','actionUrl' => route('members.create')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Belum ada anggota keluarga','description' => 'Tambahkan anggota pertama untuk mulai membangun pohon keluarga.','action-label' => 'Tambah anggota','action-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('members.create'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $attributes = $__attributesOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__attributesOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $component = $__componentOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__componentOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
    <?php else: ?>
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'mb-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mb-3']); ?>
            <form method="GET" action="<?php echo e(route('tree.index')); ?>" class="row g-3 align-items-end" aria-label="Pengaturan pohon keluarga">
                <div class="col-12 col-lg-3">
                    <label for="tree-root" class="form-label">Anggota akar</label>
                    <select id="tree-root" name="root" class="form-select">
                        <?php if(!$memberOptions->contains('uuid', $root->uuid)): ?><option value="<?php echo e($root->uuid); ?>"><?php echo e($root->full_name); ?></option><?php endif; ?>
                        <?php $__currentLoopData = $memberOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($member->uuid); ?>" <?php if($root->is($member)): echo 'selected'; endif; ?>><?php echo e($member->full_name); ?><?php echo e($member->nickname ? ' ('.$member->nickname.')' : ''); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-12 col-lg-2">
                    <label for="member-search" class="form-label">Cari pilihan</label>
                    <input id="member-search" name="member_search" class="form-control" value="<?php echo e($filters['member_search'] ?? ''); ?>" placeholder="Nama anggota">
                </div>
                <div class="col-6 col-lg-2">
                    <label for="tree-mode" class="form-label">Mode</label>
                    <select id="tree-mode" name="mode" class="form-select">
                        <option value="ancestor" <?php if($filters['mode'] === 'ancestor'): echo 'selected'; endif; ?>>Leluhur</option>
                        <option value="descendant" <?php if($filters['mode'] === 'descendant'): echo 'selected'; endif; ?>>Keturunan</option>
                        <option value="full" <?php if($filters['mode'] === 'full'): echo 'selected'; endif; ?>>Penuh</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="tree-depth" class="form-label">Kedalaman</label>
                    <select id="tree-depth" name="depth" class="form-select">
                        <?php $__currentLoopData = [1, 2, 3, 5, 10, 20]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $depth): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($depth); ?>" <?php if((int) $filters['depth'] === $depth): echo 'selected'; endif; ?>><?php echo e($depth); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-8 col-lg-2">
                    <label for="tree-layout" class="form-label">Tata letak</label>
                    <select id="tree-layout" name="layout" class="form-select">
                        <option value="vertical" <?php if($filters['layout'] === 'vertical'): echo 'selected'; endif; ?>>Vertikal</option>
                        <option value="horizontal" <?php if($filters['layout'] === 'horizontal'): echo 'selected'; endif; ?>>Horizontal</option>
                        <option value="compact" <?php if($filters['layout'] === 'compact'): echo 'selected'; endif; ?>>Ringkas</option>
                    </select>
                </div>
                <div class="col-4 col-lg-1 d-grid"><button class="btn btn-primary" type="submit">Tampilkan</button></div>
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-3">
                        <?php $__currentLoopData = ['living_only' => 'Hanya yang hidup', 'show_photos' => 'Tampilkan foto', 'show_nicknames' => 'Nama panggilan', 'show_relationships' => 'Label hubungan']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="form-check form-switch"><input type="hidden" name="<?php echo e($name); ?>" value="0"><input class="form-check-input" type="checkbox" role="switch" name="<?php echo e($name); ?>" value="1" id="<?php echo e($name); ?>" <?php if(($filters[$name] ?? ($name === 'living_only' ? '0' : '1')) === '1'): echo 'checked'; endif; ?>><label class="form-check-label" for="<?php echo e($name); ?>"><?php echo e($label); ?></label></div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </form>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>

        <div class="tree-toolbar d-flex flex-wrap gap-2 mb-2" role="toolbar" aria-label="Kontrol tampilan pohon">
            <button class="btn btn-outline-primary btn-sm" type="button" data-tree-action="zoom-in" aria-label="Perbesar pohon">Perbesar</button>
            <button class="btn btn-outline-primary btn-sm" type="button" data-tree-action="zoom-out" aria-label="Perkecil pohon">Perkecil</button>
            <button class="btn btn-outline-primary btn-sm" type="button" data-tree-action="center" aria-label="Pusatkan pohon">Pusatkan</button>
            <button class="btn btn-outline-secondary btn-sm" type="button" data-tree-action="expand" aria-label="Tampilkan semua cabang">Buka semua</button>
            <button class="btn btn-outline-secondary btn-sm" type="button" data-tree-action="collapse" aria-label="Ringkas pohon ke anggota akar">Ringkas</button>
            <label class="visually-hidden" for="tree-search">Cari anggota dalam pohon</label>
            <input id="tree-search" class="form-control form-control-sm tree-search" type="search" placeholder="Cari nama..." autocomplete="off">
        </div>

        <div id="tree-viewer" class="tree-viewer" tabindex="0" role="application" aria-label="Pohon keluarga interaktif. Gunakan tombol panah untuk menggeser dan plus atau minus untuk memperbesar atau memperkecil.">
            <div class="tree-stage" data-tree-stage>
                <svg class="tree-edges" data-tree-edges aria-hidden="true"></svg>
                <div data-tree-nodes></div>
            </div>
            <?php if (isset($component)) { $__componentOriginal75e1eed86957debb02700b731fd7eb55 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal75e1eed86957debb02700b731fd7eb55 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.loading-state','data' => ['class' => 'tree-loading','label' => 'Memuat pohon keluarga']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('loading-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'tree-loading','label' => 'Memuat pohon keluarga']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal75e1eed86957debb02700b731fd7eb55)): ?>
<?php $attributes = $__attributesOriginal75e1eed86957debb02700b731fd7eb55; ?>
<?php unset($__attributesOriginal75e1eed86957debb02700b731fd7eb55); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal75e1eed86957debb02700b731fd7eb55)): ?>
<?php $component = $__componentOriginal75e1eed86957debb02700b731fd7eb55; ?>
<?php unset($__componentOriginal75e1eed86957debb02700b731fd7eb55); ?>
<?php endif; ?>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-body-secondary">Seret kanvas untuk menggeser. Klik anggota untuk melihat detail.</small>
            <?php if((int) $filters['depth'] < 20): ?>
                <a class="btn btn-outline-primary btn-sm" href="<?php echo e(route('tree.index', array_merge(request()->query(), ['depth' => min(20, (int) $filters['depth'] + 2)]))); ?>">Muat generasi berikutnya</a>
            <?php endif; ?>
        </div>

        <div class="offcanvas offcanvas-end" tabindex="-1" id="tree-member-drawer" aria-labelledby="tree-member-title">
            <div class="offcanvas-header"><h2 class="offcanvas-title h5" id="tree-member-title">Detail anggota</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup detail"></button></div>
            <div class="offcanvas-body" data-tree-detail></div>
        </div>
        <script type="application/json" id="tree-data"><?php echo Illuminate\Support\Js::encode($tree); ?></script>
    <?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $attributes = $__attributesOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__attributesOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $component = $__componentOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__componentOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/tree/index.blade.php ENDPATH**/ ?>