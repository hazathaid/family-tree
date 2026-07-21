@props(['title' => 'Terjadi kesalahan', 'code' => null, 'message' => 'Permintaan Anda belum dapat diproses.'])
<x-layouts.base :title="$title">
    <main id="main-content" class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="text-center" style="max-width: 34rem">
            @if ($code)<p class="display-1 fw-bold text-primary mb-2" aria-hidden="true">{{ $code }}</p>@endif
            <h1 class="h2">{{ $title }}</h1>
            <p class="text-secondary mb-4">{{ $message }}</p>
            {{ $slot }}
        </div>
    </main>
</x-layouts.base>
