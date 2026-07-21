@props(['title' => null])
<x-layouts.base :title="$title">
    <x-navigation.top variant="authenticated" />
    <div class="app-shell d-flex">
        <x-navigation.sidebar />
        <main id="main-content" class="flex-grow-1 p-3 p-md-4 overflow-hidden">{{ $slot }}</main>
    </div>
    <x-navigation.mobile />
</x-layouts.base>
