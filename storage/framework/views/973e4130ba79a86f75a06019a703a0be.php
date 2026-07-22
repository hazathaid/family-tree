<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['mobile' => false]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['mobile' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard'],
        ['label' => 'Family Tree', 'route' => 'tree.index', 'pattern' => 'tree.*'],
        ['label' => 'Anggota', 'route' => 'members.index', 'pattern' => 'members.*'],
        ['label' => 'Artikel', 'route' => 'articles.index', 'pattern' => 'articles.*'],
        ['label' => 'Foto', 'route' => 'photos.index', 'pattern' => 'photos.*'],
        ['label' => 'Acara', 'route' => 'events.index', 'pattern' => 'events.*'],
        ['label' => 'Timeline', 'route' => 'timeline.index', 'pattern' => 'timeline.*'],
        ['label' => 'Pencarian', 'route' => 'search.index', 'pattern' => 'search.*'],
        ['label' => 'Laporan', 'route' => 'reports.index', 'pattern' => 'reports.*'],
        ['label' => 'Profil Saya', 'route' => 'profile.show', 'pattern' => 'profile.*'],
        ['label' => 'Pengaturan', 'route' => 'settings.index', 'pattern' => 'settings.*'],
    ];
    if (auth()->user()?->can('administer')) {
        $items[] = ['label' => 'Administrasi', 'route' => 'admin.dashboard', 'pattern' => 'admin.*'];
    }
?>
<aside class="<?php echo e($mobile ? '' : 'app-sidebar d-none d-lg-block p-3'); ?>" aria-label="Navigasi aplikasi">
    <nav class="nav nav-pills flex-column gap-1">
        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php ($active = request()->routeIs($item['pattern'])); ?>
            <a class="nav-link <?php echo e($active ? 'active' : ''); ?>" href="<?php echo e(Route::has($item['route']) ? route($item['route']) : '#'); ?>" <?php if($active): ?> aria-current="page" <?php endif; ?>><?php echo e($item['label']); ?></a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </nav>
</aside>
<?php /**PATH /var/www/html/resources/views/components/navigation/sidebar.blade.php ENDPATH**/ ?>