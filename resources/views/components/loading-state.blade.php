@props(['label' => 'Memuat data', 'lines' => 3])
<div {{ $attributes->merge(['role' => 'status', 'aria-live' => 'polite', 'aria-label' => $label]) }}>
    <span class="visually-hidden">{{ $label }}</span>
    @for($line = 0; $line < $lines; $line++)<div class="loading-skeleton rounded mb-2" style="width: {{ 100 - ($line * 12) }}%" aria-hidden="true"></div>@endfor
</div>
