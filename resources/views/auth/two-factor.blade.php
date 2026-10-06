<x-layouts.guest title="Verifikasi Dua Faktor">
    <x-card title="Verifikasi dua faktor" subtitle="Masukkan kode dari aplikasi autentikator Anda.">
        @if ($errors->any())<x-alert variant="danger">{{ $errors->first() }}</x-alert>@endif
        <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="vstack gap-3">
            @csrf
            <x-form.input name="code" label="Kode 6 digit" inputmode="numeric" autocomplete="one-time-code" autofocus />
            <x-form.input name="recovery_code" label="Kode pemulihan (opsional)" autocomplete="off" />
            <x-button type="submit">Verifikasi</x-button>
        </form>
        <div class="mt-3 small">
            <a href="{{ route('login') }}">Kembali ke halaman masuk</a>
        </div>
    </x-card>
</x-layouts.guest>
