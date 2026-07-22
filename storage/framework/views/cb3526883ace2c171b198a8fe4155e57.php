<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['variant' => 'public']));

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

foreach (array_filter((['variant' => 'public']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<header class="navbar navbar-expand border-bottom bg-white sticky-top" aria-label="Navigasi utama">
    <div class="container-fluid px-3 px-lg-4">
        <?php if($variant === 'authenticated'): ?>
            <button class="btn btn-outline-primary d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobile-navigation" aria-controls="mobile-navigation" aria-label="Buka menu navigasi">
                <span aria-hidden="true">☰</span>
            </button>
        <?php endif; ?>
        <a class="navbar-brand fw-bold text-primary text-wrap" href="<?php echo e(route('home')); ?>">Family Tree Indonesia</a>
        <nav class="ms-auto d-flex align-items-center gap-2" aria-label="Navigasi akun">
            <?php if($variant === 'authenticated'): ?>
                <?php if(Route::has('search.index')): ?>
                    <form class="d-none d-md-flex" method="GET" action="<?php echo e(route('search.index')); ?>" role="search">
                        <label class="visually-hidden" for="global-search">Cari anggota, artikel, atau acara</label>
                        <input class="form-control form-control-sm" id="global-search" name="keyword" value="<?php echo e(request()->routeIs('search.*') ? request('keyword') : ''); ?>" placeholder="Cari keluarga…" maxlength="100">
                    </form>
                <?php endif; ?>
                <?php if(Route::has('notifications.index')): ?><div class="dropdown"><button class="btn btn-link position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi, <?php echo e($navigationUnreadCount); ?> belum dibaca">🔔<?php if($navigationUnreadCount): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo e($navigationUnreadCount); ?></span><?php endif; ?></button><div class="dropdown-menu dropdown-menu-end p-2" style="min-width:18rem"><?php $__empty_1 = true; $__currentLoopData = $navigationNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><a class="dropdown-item text-wrap" href="<?php echo e(route('notifications.index')); ?>"><strong><?php echo e($notification->title); ?></strong><br><small><?php echo e(Str::limit($notification->body, 60)); ?></small></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><span class="dropdown-item-text text-secondary">Tidak ada notifikasi</span><?php endif; ?><a class="dropdown-item text-primary border-top mt-2" href="<?php echo e(route('notifications.index')); ?>">Lihat semua</a></div></div><?php endif; ?>
                <a class="d-none d-md-inline text-secondary text-decoration-none" href="<?php echo e(route('profile.show')); ?>"><?php echo e(auth()->user()?->name ?? 'Keluarga'); ?></a>
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="btn btn-outline-primary btn-sm" type="submit">Keluar</button>
                </form>
            <?php else: ?>
                <a class="btn btn-link text-decoration-none" href="<?php echo e(Route::has('login') ? route('login') : '#'); ?>">Masuk</a>
                <a class="btn btn-primary" href="<?php echo e(Route::has('register') ? route('register') : '#'); ?>">Daftar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php /**PATH /var/www/html/resources/views/components/navigation/top.blade.php ENDPATH**/ ?>