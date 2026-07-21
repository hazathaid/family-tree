@props(['title', 'message', 'icon' => '♧'])
<div {{ $attributes->class('text-center py-5 px-3') }}>
    <div class="empty-state-icon mb-3" aria-hidden="true">{{ $icon }}</div>
    <h2 class="h5">{{ $title }}</h2>
    <p class="text-secondary mx-auto" style="max-width: 32rem">{{ $message }}</p>
    @isset($action)<div class="mt-3">{{ $action }}</div>@endisset
</div>
