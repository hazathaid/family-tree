@props(['title' => null, 'subtitle' => null, 'padding' => true])
<section {{ $attributes->class('card') }}>
    @if($title || $subtitle)
        <div class="card-header bg-white">
            @if($title)<h2 class="h5 mb-0">{{ $title }}</h2>@endif
            @if($subtitle)<p class="text-secondary small mb-0 mt-1">{{ $subtitle }}</p>@endif
        </div>
    @endif
    <div @class(['card-body' => $padding])>{{ $slot }}</div>
</section>
