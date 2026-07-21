<x-layouts.guest title="Lupa kata sandi">
    <x-card title="Atur ulang kata sandi" subtitle="Kami akan mengirim tautan pemulihan ke email Anda.">
        @if (session('status'))<x-alert variant="success">{{ session('status') }}</x-alert>@endif
        <form method="POST" action="{{ route('password.email') }}" class="vstack gap-3">
            @csrf
            <x-form.input name="email" label="Email" type="email" autocomplete="email" required autofocus />
            <x-button type="submit">Kirim tautan</x-button>
        </form>
        <p class="small mt-3 mb-0"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
    </x-card>
</x-layouts.guest>
