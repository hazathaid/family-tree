<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'required' => false]));

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

foreach (array_filter((['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'required' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php ($inputId = $attributes->get('id', $name)); ?>
<div>
    <label class="form-label" for="<?php echo e($inputId); ?>"><?php echo e($label); ?> <?php if($required): ?><span class="text-danger" aria-hidden="true">*</span><?php endif; ?></label>
    <input
        <?php echo e($attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($name)])->merge(['id' => $inputId, 'name' => $name, 'type' => $type, 'value' => old($name, $value)])); ?>

        <?php if($required): ?> required aria-required="true" <?php endif; ?>
        <?php if($errors->has($name)): ?> aria-invalid="true" aria-describedby="<?php echo e($inputId); ?>-error" <?php elseif($help): ?> aria-describedby="<?php echo e($inputId); ?>-help" <?php endif; ?>
    >
    <?php $__errorArgs = [$name];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div id="<?php echo e($inputId); ?>-error" class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    <?php if($help && !$errors->has($name)): ?><div id="<?php echo e($inputId); ?>-help" class="form-text"><?php echo e($help); ?></div><?php endif; ?>
</div>
<?php /**PATH /var/www/html/resources/views/components/form/input.blade.php ENDPATH**/ ?>