<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title' => null, 'description' => null]));

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

foreach (array_filter((['title' => null, 'description' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php if (isset($component)) { $__componentOriginald4c772c02301431d3253f64117700596 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c772c02301431d3253f64117700596 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.base','data' => ['title' => $title,'description' => $description ?? 'Platform untuk membangun, menjaga, dan mewariskan sejarah keluarga Indonesia.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.base'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($title),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($description ?? 'Platform untuk membangun, menjaga, dan mewariskan sejarah keluarga Indonesia.')]); ?>
    <?php if (isset($component)) { $__componentOriginal98fccca82b9a2ba79b46ceb95922696d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal98fccca82b9a2ba79b46ceb95922696d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.navigation.top','data' => ['variant' => 'public']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('navigation.top'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'public']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal98fccca82b9a2ba79b46ceb95922696d)): ?>
<?php $attributes = $__attributesOriginal98fccca82b9a2ba79b46ceb95922696d; ?>
<?php unset($__attributesOriginal98fccca82b9a2ba79b46ceb95922696d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal98fccca82b9a2ba79b46ceb95922696d)): ?>
<?php $component = $__componentOriginal98fccca82b9a2ba79b46ceb95922696d; ?>
<?php unset($__componentOriginal98fccca82b9a2ba79b46ceb95922696d); ?>
<?php endif; ?>
    <main id="main-content"><?php echo e($slot); ?></main>
    <?php if (isset($component)) { $__componentOriginal901074e185567f5f1d92866b8152d9bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal901074e185567f5f1d92866b8152d9bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.navigation.footer','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('navigation.footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal901074e185567f5f1d92866b8152d9bb)): ?>
<?php $attributes = $__attributesOriginal901074e185567f5f1d92866b8152d9bb; ?>
<?php unset($__attributesOriginal901074e185567f5f1d92866b8152d9bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal901074e185567f5f1d92866b8152d9bb)): ?>
<?php $component = $__componentOriginal901074e185567f5f1d92866b8152d9bb; ?>
<?php unset($__componentOriginal901074e185567f5f1d92866b8152d9bb); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald4c772c02301431d3253f64117700596)): ?>
<?php $attributes = $__attributesOriginald4c772c02301431d3253f64117700596; ?>
<?php unset($__attributesOriginald4c772c02301431d3253f64117700596); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald4c772c02301431d3253f64117700596)): ?>
<?php $component = $__componentOriginald4c772c02301431d3253f64117700596; ?>
<?php unset($__componentOriginald4c772c02301431d3253f64117700596); ?>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/components/layouts/public.blade.php ENDPATH**/ ?>