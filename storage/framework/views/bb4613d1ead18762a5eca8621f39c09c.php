<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['variant' => 'info', 'dismissible' => false]));

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

foreach (array_filter((['variant' => 'info', 'dismissible' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div <?php echo e($attributes->class(['alert', 'alert-'.$variant, 'alert-dismissible fade show' => $dismissible])->merge(['role' => 'alert'])); ?>>
    <?php echo e($slot); ?>

    <?php if($dismissible): ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup pesan"></button><?php endif; ?>
</div>
<?php /**PATH /var/www/html/resources/views/components/alert.blade.php ENDPATH**/ ?>