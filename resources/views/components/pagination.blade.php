@props(['paginator'])
@if ($paginator->hasPages())
    <nav aria-label="Navigasi halaman">{{ $paginator->onEachSide(1)->withQueryString()->links() }}</nav>
@endif
