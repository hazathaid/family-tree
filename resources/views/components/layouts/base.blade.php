@props([
    'title' => null,
    'description' => 'Platform untuk membangun, menjaga, dan mewariskan sejarah keluarga Indonesia.',
    'bodyClass' => '',
])
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description }}">
    <title>{{ $title ? $title.' | ' : '' }}Family Tree Platform Indonesia</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ $bodyClass }}">
    <a class="skip-link btn btn-primary" href="#main-content">Lewati ke konten utama</a>
    {{ $slot }}
</body>
</html>
