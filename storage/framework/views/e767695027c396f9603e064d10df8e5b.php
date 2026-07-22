<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['variant' => 'primary', 'type' => 'button', 'href' => null, 'size' => null]));

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

foreach (array_filter((['variant' => 'primary', 'type' => 'button', 'href' => null, 'size' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php ($classes = 'btn btn-'.$variant.($size ? ' btn-'.$size : '')); ?>
<?php if($href): ?>
    <a <?php echo e($attributes->class($classes)->merge(['href' => $href])); ?>><?php echo e($slot); ?></a>
<?php else: ?>
    <button <?php echo e($attributes->class($classes)->merge(['type' => $type])); ?>><?php echo e($slot); ?></button>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/components/button.blade.php ENDPATH**/ ?>