@props(['variant' => 'public'])
<header class="navbar navbar-expand border-bottom bg-white sticky-top" aria-label="Navigasi utama">
    <div class="container-fluid px-3 px-lg-4">
        @if ($variant === 'authenticated')
            <button class="btn btn-outline-primary d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobile-navigation" aria-controls="mobile-navigation" aria-label="Buka menu navigasi">
                <span aria-hidden="true">☰</span>
            </button>
        @endif
        <a class="navbar-brand fw-bold text-primary text-wrap" href="{{ route('home') }}">Family Tree Indonesia</a>
        <nav class="ms-auto d-flex align-items-center gap-2" aria-label="Navigasi akun">
            @if ($variant === 'authenticated')
                @if(Route::has('notifications.index'))<div class="dropdown"><button class="btn btn-link position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi, {{ $navigationUnreadCount }} belum dibaca">🔔@if($navigationUnreadCount)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $navigationUnreadCount }}</span>@endif</button><div class="dropdown-menu dropdown-menu-end p-2" style="min-width:18rem">@forelse($navigationNotifications as $notification)<a class="dropdown-item text-wrap" href="{{ route('notifications.index') }}"><strong>{{ $notification->title }}</strong><br><small>{{ Str::limit($notification->body, 60) }}</small></a>@empty<span class="dropdown-item-text text-secondary">Tidak ada notifikasi</span>@endforelse<a class="dropdown-item text-primary border-top mt-2" href="{{ route('notifications.index') }}">Lihat semua</a></div></div>@endif
                <span class="d-none d-md-inline text-secondary">{{ auth()->user()?->name ?? 'Keluarga' }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-primary btn-sm" type="submit">Keluar</button>
                </form>
            @else
                <a class="btn btn-link text-decoration-none" href="{{ Route::has('login') ? route('login') : '#' }}">Masuk</a>
                <a class="btn btn-primary" href="{{ Route::has('register') ? route('register') : '#' }}">Daftar</a>
            @endif
        </nav>
    </div>
</header>
