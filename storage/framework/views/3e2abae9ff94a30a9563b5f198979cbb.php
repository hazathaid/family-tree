<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title', 'message', 'icon' => '♧']));

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

foreach (array_filter((['title', 'message', 'icon' => '♧']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div <?php echo e($attributes->class('text-center py-5 px-3')); ?>>
    <div class="empty-state-icon mb-3" aria-hidden="true"><?php echo e($icon); ?></div>
    <h2 class="h5"><?php echo e($title); ?></h2>
    <p class="text-secondary mx-auto" style="max-width: 32rem"><?php echo e($message); ?></p>
    <?php if(isset($action)): ?><div class="mt-3"><?php echo e($action); ?></div><?php endif; ?>
</div>
<?php /**PATH /var/www/html/resources/views/components/empty-state.blade.php ENDPATH**/ ?>