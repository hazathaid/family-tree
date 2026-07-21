<x-layouts.error title="Sesi telah berakhir" code="419" message="Muat ulang halaman, lalu ulangi tindakan Anda.">
    <a class="btn btn-primary" href="{{ url()->previous() === url()->current() ? route('home') : url()->previous() }}">Muat kembali</a>
</x-layouts.error>
