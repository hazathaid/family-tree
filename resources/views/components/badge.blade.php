@props(['variant' => 'primary'])
<span {{ $attributes->class('badge text-bg-'.$variant) }}>{{ $slot }}</span>
