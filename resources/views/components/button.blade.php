@props(['variant' => 'primary', 'type' => 'button', 'href' => null, 'size' => null])
@php($classes = 'btn btn-'.$variant.($size ? ' btn-'.$size : ''))
@if ($href)
    <a {{ $attributes->class($classes)->merge(['href' => $href]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->class($classes)->merge(['type' => $type]) }}>{{ $slot }}</button>
@endif
