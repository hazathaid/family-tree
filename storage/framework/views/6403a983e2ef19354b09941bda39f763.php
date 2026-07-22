<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label' => 'Memuat data', 'lines' => 3]));

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

foreach (array_filter((['label' => 'Memuat data', 'lines' => 3]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div <?php echo e($attributes->merge(['role' => 'status', 'aria-live' => 'polite', 'aria-label' => $label])); ?>>
    <span class="visually-hidden"><?php echo e($label); ?></span>
    <?php for($line = 0; $line < $lines; $line++): ?><div class="loading-skeleton rounded mb-2" style="width: <?php echo e(100 - ($line * 12)); ?>%" aria-hidden="true"></div><?php endfor; ?>
</div>
<?php /**PATH /var/www/html/resources/views/components/loading-state.blade.php ENDPATH**/ ?>