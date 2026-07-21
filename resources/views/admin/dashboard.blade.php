<x-layouts.app title="Administrasi">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><p class="text-primary fw-semibold mb-1">Super Admin</p><h1 class="h2 mb-0">Administrasi sistem</h1></div>
    </div>
    <div class="row g-3 mb-4" aria-label="Ringkasan administrasi">
        @foreach ([['Pengguna', $statistics['users']], ['Pengguna ditangguhkan', $statistics['suspended_users']], ['Keluarga', $statistics['families']]] as [$label, $value])
            <div class="col-md-4"><x-card class="h-100"><p class="text-secondary mb-2">{{ $label }}</p><p class="display-6 fw-bold mb-0">{{ number_format($value) }}</p></x-card></div>
        @endforeach
    </div>
    <div class="row g-3">
        <div class="col-md-4"><x-card class="h-100"><h2 class="h5">Kelola pengguna</h2><p class="text-secondary">Tinjau akun dan ubah status akses pengguna.</p><a class="btn btn-primary" href="{{ route('admin.users.index') }}">Buka pengguna</a></x-card></div>
        <div class="col-md-4"><x-card class="h-100"><h2 class="h5">Moderasi keluarga</h2><p class="text-secondary">Tinjau keluarga dan hapus konten yang melanggar.</p><a class="btn btn-primary" href="{{ route('admin.families.index') }}">Buka moderasi</a></x-card></div>
        <div class="col-md-4"><x-card class="h-100"><h2 class="h5">Audit log</h2><p class="text-secondary">Telusuri dan ekspor tindakan kritis sistem.</p><a class="btn btn-primary" href="{{ route('admin.audit-logs.index') }}">Buka audit log</a></x-card></div>
    </div>
</x-layouts.app>
