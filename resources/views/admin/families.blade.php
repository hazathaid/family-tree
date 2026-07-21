<x-layouts.app title="Moderasi Keluarga">
    <div class="mb-4"><a href="{{ route('admin.dashboard') }}">← Administrasi</a><h1 class="h2 mt-2">Moderasi keluarga</h1></div>
    <div class="row g-3">
        @forelse($families as $family)<div class="col-md-6 col-xl-4"><x-card class="h-100"><h2 class="h5">{{ $family->name }}</h2><p class="text-secondary small">Pemilik: {{ $family->creator?->email ?? 'Tidak tersedia' }}</p><dl class="row small"><dt class="col-7">Anggota</dt><dd class="col-5 text-end">{{ $family->members_count }}</dd><dt class="col-7">Artikel</dt><dd class="col-5 text-end">{{ $family->articles_count }}</dd><dt class="col-7">Foto</dt><dd class="col-5 text-end">{{ $family->photos_count }}</dd><dt class="col-7">Acara</dt><dd class="col-5 text-end">{{ $family->events_count }}</dd></dl><a class="btn btn-outline-primary" href="{{ route('admin.families.show', $family) }}">Tinjau keluarga</a></x-card></div>
        @empty<div class="col-12"><x-empty-state title="Belum ada keluarga" message="Keluarga yang terdaftar akan tampil di sini." /></div>@endforelse
    </div><div class="mt-3"><x-pagination :paginator="$families" /></div>
</x-layouts.app>
