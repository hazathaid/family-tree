<x-layouts.app title="Dashboard">
    @if (session('status'))<x-alert variant="success">{{ session('status') }}</x-alert>@endif
    <x-card title="Dashboard keluarga" subtitle="Ringkasan keluarga akan tersedia pada Phase 17 Step 3.">
        <p class="mb-0">Keluarga aktif telah dipilih.</p>
    </x-card>
</x-layouts.app>
