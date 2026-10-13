<x-layouts.app title="Silsilah {{ $partnerFamily->name }}">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-primary fw-semibold mb-1">{{ $family->name }} &rarr; {{ $partnerFamily->name }}</p>
            <h1 class="h3 mb-1">Silsilah {{ $partnerFamily->name }}</h1>
            <p class="text-body-secondary mb-0">Baca-saja melalui tautan ke {{ $anchor->full_name }}. Anggota yang masih hidup disamarkan sesuai kebijakan privasi.</p>
        </div>
        @if($tree)
            <div class="d-flex gap-2" aria-label="Statistik pohon">
                <x-badge variant="secondary">Baca-saja</x-badge>
                <x-badge variant="primary">{{ $tree['statistics']['members'] }} anggota</x-badge>
                <x-badge variant="secondary">{{ $tree['statistics']['generations'] }} generasi</x-badge>
            </div>
        @endif
    </div>

    <x-alert variant="info">Anda melihat keluarga ini melalui tautan pernikahan yang disetujui. Data tidak dapat diubah dan anggota yang masih hidup ditampilkan sebagai &ldquo;Anggota Hidup&rdquo;.</x-alert>

    <div class="tree-toolbar d-flex flex-wrap gap-2 mb-2" role="toolbar" aria-label="Kontrol tampilan pohon">
        <button class="btn btn-outline-primary btn-sm" type="button" data-tree-action="zoom-in" aria-label="Perbesar pohon">Perbesar</button>
        <button class="btn btn-outline-primary btn-sm" type="button" data-tree-action="zoom-out" aria-label="Perkecil pohon">Perkecil</button>
        <button class="btn btn-outline-primary btn-sm" type="button" data-tree-action="center" aria-label="Pusatkan pohon">Pusatkan</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-tree-action="expand" aria-label="Tampilkan semua cabang">Buka semua</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-tree-action="collapse" aria-label="Ringkas pohon ke anggota akar">Ringkas</button>
        <label class="visually-hidden" for="tree-search">Cari anggota dalam pohon</label>
        <input id="tree-search" class="form-control form-control-sm tree-search" type="search" placeholder="Cari nama..." autocomplete="off">
    </div>

    <div class="d-none" aria-hidden="true">
        <input type="checkbox" id="show_photos" checked tabindex="-1">
        <input type="checkbox" id="show_nicknames" checked tabindex="-1">
        <input type="checkbox" id="show_relationships" checked tabindex="-1">
        <input type="checkbox" id="living_only" tabindex="-1">
    </div>

    <div id="tree-viewer" class="tree-viewer" data-readonly="true" tabindex="0" role="application" aria-label="Silsilah keluarga tertaut, baca-saja. Gunakan tombol panah untuk menggeser dan plus atau minus untuk memperbesar.">
        <div class="tree-stage" data-tree-stage>
            <svg class="tree-edges" data-tree-edges aria-hidden="true"></svg>
            <div data-tree-nodes></div>
        </div>
        <x-loading-state class="tree-loading" label="Memuat silsilah" />
    </div>

    <p class="text-body-secondary small mt-3">Seret kanvas untuk menggeser. Klik anggota untuk melihat detail baca-saja. <a href="{{ route('family-links.index') }}">Kembali ke daftar tautan</a>.</p>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="tree-member-drawer" aria-labelledby="tree-member-title">
        <div class="offcanvas-header"><h2 class="offcanvas-title h5" id="tree-member-title">Detail anggota</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup detail"></button></div>
        <div class="offcanvas-body" data-tree-detail></div>
    </div>
    <script type="application/json" id="tree-data">{!! Illuminate\Support\Js::encode($tree) !!}</script>
</x-layouts.app>
