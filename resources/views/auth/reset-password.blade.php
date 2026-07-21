<x-layouts.guest title="Kata sandi baru">
    <x-card title="Buat kata sandi baru">
        <form method="POST" action="{{ route('password.update') }}" class="vstack gap-3">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-form.input name="email" label="Email" type="email" :value="$email" autocomplete="email" required />
            <x-form.input name="password" label="Kata sandi baru" type="password" autocomplete="new-password" required />
            <x-form.input name="password_confirmation" label="Konfirmasi kata sandi" type="password" autocomplete="new-password" required />
            <x-button type="submit">Simpan kata sandi</x-button>
        </form>
    </x-card>
</x-layouts.guest>
