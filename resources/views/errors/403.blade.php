<x-layouts.error title="Akses ditolak" code="403" message="Anda tidak memiliki izin untuk membuka halaman ini.">
    <a class="btn btn-primary" href="{{ url()->previous() === url()->current() ? route('home') : url()->previous() }}">Kembali</a>
</x-layouts.error>
