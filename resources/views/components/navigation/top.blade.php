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
                <span class="d-none d-md-inline text-secondary">{{ auth()->user()?->name ?? 'Keluarga' }}</span>
            @else
                <a class="btn btn-link text-decoration-none" href="{{ Route::has('login') ? route('login') : '#' }}">Masuk</a>
                <a class="btn btn-primary" href="{{ Route::has('register') ? route('register') : '#' }}">Daftar</a>
            @endif
        </nav>
    </div>
</header>
