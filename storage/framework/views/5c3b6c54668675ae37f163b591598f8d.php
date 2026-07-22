<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title' => null]));

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

foreach (array_filter((['title' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php if (isset($component)) { $__componentOriginald4c772c02301431d3253f64117700596 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c772c02301431d3253f64117700596 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.base','data' => ['title' => $title]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.base'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($title)]); ?>
    <?php if (isset($component)) { $__componentOriginal98fccca82b9a2ba79b46ceb95922696d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal98fccca82b9a2ba79b46ceb95922696d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.navigation.top','data' => ['variant' => 'authenticated']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('navigation.top'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'authenticated']); ?>
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
    <div class="app-shell d-flex">
        <?php if (isset($component)) { $__componentOriginal9d69c11139dac45c50995da6fd1bec81 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9d69c11139dac45c50995da6fd1bec81 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.navigation.sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('navigation.sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9d69c11139dac45c50995da6fd1bec81)): ?>
<?php $attributes = $__attributesOriginal9d69c11139dac45c50995da6fd1bec81; ?>
<?php unset($__attributesOriginal9d69c11139dac45c50995da6fd1bec81); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9d69c11139dac45c50995da6fd1bec81)): ?>
<?php $component = $__componentOriginal9d69c11139dac45c50995da6fd1bec81; ?>
<?php unset($__componentOriginal9d69c11139dac45c50995da6fd1bec81); ?>
<?php endif; ?>
        <main id="main-content" class="flex-grow-1 p-3 p-md-4 overflow-hidden"><?php echo e($slot); ?></main>
    </div>
    <?php if (isset($component)) { $__componentOriginal48c54f4a65bf735542937c9223e8e6fc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal48c54f4a65bf735542937c9223e8e6fc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.navigation.mobile','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('navigation.mobile'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal48c54f4a65bf735542937c9223e8e6fc)): ?>
<?php $attributes = $__attributesOriginal48c54f4a65bf735542937c9223e8e6fc; ?>
<?php unset($__attributesOriginal48c54f4a65bf735542937c9223e8e6fc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal48c54f4a65bf735542937c9223e8e6fc)): ?>
<?php $component = $__componentOriginal48c54f4a65bf735542937c9223e8e6fc; ?>
<?php unset($__componentOriginal48c54f4a65bf735542937c9223e8e6fc); ?>
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
<?php /**PATH /var/www/html/resources/views/components/layouts/app.blade.php ENDPATH**/ ?>