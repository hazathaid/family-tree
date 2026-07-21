<x-layouts.error title="Data tidak dapat diproses" code="422" message="Periksa kembali data yang dikirim dan coba lagi.">
    <a class="btn btn-primary" href="{{ url()->previous() === url()->current() ? route('home') : url()->previous() }}">Periksa kembali</a>
</x-layouts.error>
