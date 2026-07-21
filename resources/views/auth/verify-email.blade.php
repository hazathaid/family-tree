<x-layouts.guest title="Verifikasi email">
    <x-card title="Verifikasi email Anda" subtitle="Buka tautan yang kami kirim sebelum melanjutkan.">
        @if (session('status'))<x-alert variant="success">{{ session('status') }}</x-alert>@endif
        <p class="text-secondary">Belum menerima email? Periksa folder spam atau kirim ulang tautan.</p>
        <form method="POST" action="{{ route('verification.send') }}" class="d-flex flex-wrap gap-2">
            @csrf
            <x-button type="submit">Kirim ulang</x-button>
        </form>
    </x-card>
</x-layouts.guest>
