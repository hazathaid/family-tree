@props(['title' => null])
<x-layouts.base :title="$title">
    <x-navigation.top variant="guest" />
    <main id="main-content" class="container py-4 py-md-5">
        <div class="mx-auto" style="max-width: 32rem">{{ $slot }}</div>
    </main>
    <x-navigation.footer />
</x-layouts.base>
