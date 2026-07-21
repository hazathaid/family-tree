<x-layouts.guest title="Daftar">
    <x-card title="Buat akun" subtitle="Mulai dokumentasikan keluarga Anda.">
        <form method="POST" action="{{ route('register') }}" class="vstack gap-3">
            @csrf
            <x-form.input name="name" label="Nama" autocomplete="name" required autofocus />
            <x-form.input name="email" label="Email" type="email" autocomplete="email" required />
            <x-form.input name="phone" label="Nomor HP" type="tel" autocomplete="tel" />
            <x-form.input name="password" label="Kata sandi" type="password" autocomplete="new-password" help="Minimal 8 karakter." required />
            <x-form.input name="password_confirmation" label="Konfirmasi kata sandi" type="password" autocomplete="new-password" required />
            <x-button type="submit">Daftar</x-button>
        </form>
        <p class="small text-center mt-3 mb-0">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
    </x-card>
</x-layouts.guest>
