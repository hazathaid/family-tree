<x-layouts.guest title="Masuk">
    <x-card title="Masuk" subtitle="Lanjutkan perjalanan sejarah keluarga Anda.">
        @if (session('status'))<x-alert variant="success">{{ session('status') }}</x-alert>@endif
        <form method="POST" action="{{ route('login') }}" class="vstack gap-3">
            @csrf
            <x-form.input name="email" label="Email" type="email" autocomplete="email" required autofocus />
            <x-form.input name="password" label="Kata sandi" type="password" autocomplete="current-password" required />
            <div class="form-check">
                <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                <label class="form-check-label" for="remember">Ingat saya</label>
            </div>
            <x-button type="submit">Masuk</x-button>
        </form>
        <div class="d-flex flex-wrap justify-content-between gap-2 mt-3 small">
            <a href="{{ route('password.request') }}">Lupa kata sandi?</a>
            <a href="{{ route('register') }}">Belum punya akun?</a>
        </div>
    </x-card>
</x-layouts.guest>
