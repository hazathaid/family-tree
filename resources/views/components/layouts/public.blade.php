@props(['title' => null, 'description' => null])
<x-layouts.base :title="$title" :description="$description ?? 'Platform untuk membangun, menjaga, dan mewariskan sejarah keluarga Indonesia.'">
    <x-navigation.top variant="public" />
    <main id="main-content">{{ $slot }}</main>
    <x-navigation.footer />
</x-layouts.base>
