@props(['mobile' => false])
@php
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard'],
        ['label' => 'Family Tree', 'route' => 'tree.index', 'pattern' => 'tree.*'],
        ['label' => 'Anggota', 'route' => 'members.index', 'pattern' => 'members.*'],
        ['label' => 'Artikel', 'route' => 'articles.index', 'pattern' => 'articles.*'],
        ['label' => 'Foto', 'route' => 'photos.index', 'pattern' => 'photos.*'],
        ['label' => 'Acara', 'route' => 'events.index', 'pattern' => 'events.*'],
        ['label' => 'Timeline', 'route' => 'timeline.index', 'pattern' => 'timeline.*'],
        ['label' => 'Pencarian', 'route' => 'search.index', 'pattern' => 'search.*'],
        ['label' => 'Laporan', 'route' => 'reports.index', 'pattern' => 'reports.*'],
        ['label' => 'Profil Saya', 'route' => 'profile.show', 'pattern' => 'profile.*'],
        ['label' => 'Pengaturan', 'route' => 'settings.index', 'pattern' => 'settings.*'],
    ];
@endphp
<aside class="{{ $mobile ? '' : 'app-sidebar d-none d-lg-block p-3' }}" aria-label="Navigasi aplikasi">
    <nav class="nav nav-pills flex-column gap-1">
        @foreach ($items as $item)
            @php($active = request()->routeIs($item['pattern']))
            <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}" @if($active) aria-current="page" @endif>{{ $item['label'] }}</a>
        @endforeach
    </nav>
</aside>
