<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => null,
    'description' => 'Platform untuk membangun, menjaga, dan mewariskan sejarah keluarga Indonesia.',
    'bodyClass' => '',
]));

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

foreach (array_filter(([
    'title' => null,
    'description' => 'Platform untuk membangun, menjaga, dan mewariskan sejarah keluarga Indonesia.',
    'bodyClass' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo e($description); ?>">
    <title><?php echo e($title ? $title.' | ' : ''); ?>Family Tree Platform Indonesia</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="<?php echo e($bodyClass); ?>">
    <a class="skip-link btn btn-primary" href="#main-content">Lewati ke konten utama</a>
    <?php echo e($slot); ?>

</body>
</html>
<?php /**PATH /var/www/html/resources/views/components/layouts/base.blade.php ENDPATH**/ ?>