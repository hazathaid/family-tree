<?php if (isset($component)) { $__componentOriginal74e861309dfc9b9ab6d1aa2ec05b6057 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74e861309dfc9b9ab6d1aa2ec05b6057 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.error','data' => ['title' => 'Akses ditolak','code' => '403','message' => 'Anda tidak memiliki izin untuk membuka halaman ini.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Akses ditolak','code' => '403','message' => 'Anda tidak memiliki izin untuk membuka halaman ini.']); ?>
    <a class="btn btn-primary" href="<?php echo e(url()->previous() === url()->current() ? route('home') : url()->previous()); ?>">Kembali</a>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74e861309dfc9b9ab6d1aa2ec05b6057)): ?>
<?php $attributes = $__attributesOriginal74e861309dfc9b9ab6d1aa2ec05b6057; ?>
<?php unset($__attributesOriginal74e861309dfc9b9ab6d1aa2ec05b6057); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74e861309dfc9b9ab6d1aa2ec05b6057)): ?>
<?php $component = $__componentOriginal74e861309dfc9b9ab6d1aa2ec05b6057; ?>
<?php unset($__componentOriginal74e861309dfc9b9ab6d1aa2ec05b6057); ?>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/errors/403.blade.php ENDPATH**/ ?>