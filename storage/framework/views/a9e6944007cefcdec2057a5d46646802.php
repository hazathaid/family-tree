<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title' => null, 'subtitle' => null, 'padding' => true]));

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

foreach (array_filter((['title' => null, 'subtitle' => null, 'padding' => true]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<section <?php echo e($attributes->class('card')); ?>>
    <?php if($title || $subtitle): ?>
        <div class="card-header bg-white">
            <?php if($title): ?><h2 class="h5 mb-0"><?php echo e($title); ?></h2><?php endif; ?>
            <?php if($subtitle): ?><p class="text-secondary small mb-0 mt-1"><?php echo e($subtitle); ?></p><?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['card-body' => $padding]); ?>"><?php echo e($slot); ?></div>
</section>
<?php /**PATH /var/www/html/resources/views/components/card.blade.php ENDPATH**/ ?>