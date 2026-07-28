@props(['member'])
@php
    $displayName = app(\App\Services\MemorialNameService::class)->displayName(
        $member->is_alive,
        $member->gender,
        $member->religion,
        $member->full_name,
    );
@endphp
{{ $displayName }}
