@props(['title' => null])
<x-layouts.base :title="$title">
    <header class="navbar navbar-expand app-topbar sticky-top" aria-label="Navigasi akses baca-saja">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand family-brand text-wrap" href="{{ route('home') }}">
                <span class="family-brand-mark" aria-hidden="true">SK</span>
                <span>Silsilahku</span>
            </a>
            <nav class="ms-auto d-flex align-items-center gap-2" aria-label="Navigasi akun">
                <x-badge variant="warning">Akses baca-saja</x-badge>
                <a class="d-none d-md-inline text-secondary text-decoration-none" href="{{ route('profile.show') }}">{{ auth()->user()?->name ?? 'Tamu' }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-primary btn-sm" type="submit">Keluar</button>
                </form>
            </nav>
        </div>
    </header>
    <main id="main-content" class="container py-4 py-md-5">{{ $slot }}</main>
    <x-navigation.footer />
</x-layouts.base>
