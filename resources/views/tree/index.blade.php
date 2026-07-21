<x-layouts.app title="Pohon Keluarga">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-primary fw-semibold mb-1">{{ $family->name }}</p>
            <h1 class="h3 mb-1">Pohon Keluarga</h1>
            <p class="text-body-secondary mb-0">Jelajahi hubungan keluarga dari anggota yang Anda pilih.</p>
        </div>
        @if($tree)
            <div class="d-flex gap-2" aria-label="Statistik pohon">
                <x-badge variant="primary">{{ $tree['statistics']['members'] }} anggota</x-badge>
                <x-badge variant="secondary">{{ $tree['statistics']['generations'] }} generasi</x-badge>
            </div>
        @endif
    </div>

    @if(!$root)
        <x-empty-state title="Belum ada anggota keluarga" description="Tambahkan anggota pertama untuk mulai membangun pohon keluarga." action-label="Tambah anggota" :action-url="route('members.create')" />
    @else
        <x-card class="mb-3">
            <form method="GET" action="{{ route('tree.index') }}" class="row g-3 align-items-end" aria-label="Pengaturan pohon keluarga">
                <div class="col-12 col-lg-3">
                    <label for="tree-root" class="form-label">Anggota akar</label>
                    <select id="tree-root" name="root" class="form-select">
                        @if(!$memberOptions->contains('uuid', $root->uuid))<option value="{{ $root->uuid }}">{{ $root->full_name }}</option>@endif
                        @foreach($memberOptions as $member)<option value="{{ $member->uuid }}" @selected($root->is($member))>{{ $member->full_name }}{{ $member->nickname ? ' ('.$member->nickname.')' : '' }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-2">
                    <label for="member-search" class="form-label">Cari pilihan</label>
                    <input id="member-search" name="member_search" class="form-control" value="{{ $filters['member_search'] ?? '' }}" placeholder="Nama anggota">
                </div>
                <div class="col-6 col-lg-2">
                    <label for="tree-mode" class="form-label">Mode</label>
                    <select id="tree-mode" name="mode" class="form-select">
                        <option value="ancestor" @selected($filters['mode'] === 'ancestor')>Leluhur</option>
                        <option value="descendant" @selected($filters['mode'] === 'descendant')>Keturunan</option>
                        <option value="full" @selected($filters['mode'] === 'full')>Penuh</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="tree-depth" class="form-label">Kedalaman</label>
                    <select id="tree-depth" name="depth" class="form-select">
                        @foreach([1, 2, 3, 5, 10, 20] as $depth)<option value="{{ $depth }}" @selected((int) $filters['depth'] === $depth)>{{ $depth }}</option>@endforeach
                    </select>
                </div>
                <div class="col-8 col-lg-2">
                    <label for="tree-layout" class="form-label">Tata letak</label>
                    <select id="tree-layout" name="layout" class="form-select">
                        <option value="vertical" @selected($filters['layout'] === 'vertical')>Vertikal</option>
                        <option value="horizontal" @selected($filters['layout'] === 'horizontal')>Horizontal</option>
                        <option value="compact" @selected($filters['layout'] === 'compact')>Ringkas</option>
                    </select>
                </div>
                <div class="col-4 col-lg-1 d-grid"><button class="btn btn-primary" type="submit">Tampilkan</button></div>
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-3">
                        @foreach(['living_only' => 'Hanya yang hidup', 'show_photos' => 'Tampilkan foto', 'show_nicknames' => 'Nama panggilan', 'show_relationships' => 'Label hubungan'] as $name => $label)
                            <div class="form-check form-switch"><input type="hidden" name="{{ $name }}" value="0"><input class="form-check-input" type="checkbox" role="switch" name="{{ $name }}" value="1" id="{{ $name }}" @checked(($filters[$name] ?? ($name === 'living_only' ? '0' : '1')) === '1')><label class="form-check-label" for="{{ $name }}">{{ $label }}</label></div>
                        @endforeach
                    </div>
                </div>
            </form>
        </x-card>

        <div class="tree-toolbar d-flex flex-wrap gap-2 mb-2" role="toolbar" aria-label="Kontrol tampilan pohon">
            <button class="btn btn-outline-primary btn-sm" type="button" data-tree-action="zoom-in" aria-label="Perbesar pohon">Perbesar</button>
            <button class="btn btn-outline-primary btn-sm" type="button" data-tree-action="zoom-out" aria-label="Perkecil pohon">Perkecil</button>
            <button class="btn btn-outline-primary btn-sm" type="button" data-tree-action="center" aria-label="Pusatkan pohon">Pusatkan</button>
            <button class="btn btn-outline-secondary btn-sm" type="button" data-tree-action="expand" aria-label="Tampilkan semua cabang">Buka semua</button>
            <button class="btn btn-outline-secondary btn-sm" type="button" data-tree-action="collapse" aria-label="Ringkas pohon ke anggota akar">Ringkas</button>
            <label class="visually-hidden" for="tree-search">Cari anggota dalam pohon</label>
            <input id="tree-search" class="form-control form-control-sm tree-search" type="search" placeholder="Cari nama..." autocomplete="off">
        </div>

        <div id="tree-viewer" class="tree-viewer" tabindex="0" role="application" aria-label="Pohon keluarga interaktif. Gunakan tombol panah untuk menggeser dan plus atau minus untuk memperbesar atau memperkecil.">
            <div class="tree-stage" data-tree-stage>
                <svg class="tree-edges" data-tree-edges aria-hidden="true"></svg>
                <div data-tree-nodes></div>
            </div>
            <x-loading-state class="tree-loading" label="Memuat pohon keluarga" />
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-body-secondary">Seret kanvas untuk menggeser. Klik anggota untuk melihat detail.</small>
            @if((int) $filters['depth'] < 20)
                <a class="btn btn-outline-primary btn-sm" href="{{ route('tree.index', array_merge(request()->query(), ['depth' => min(20, (int) $filters['depth'] + 2)])) }}">Muat generasi berikutnya</a>
            @endif
        </div>

        <div class="offcanvas offcanvas-end" tabindex="-1" id="tree-member-drawer" aria-labelledby="tree-member-title">
            <div class="offcanvas-header"><h2 class="offcanvas-title h5" id="tree-member-title">Detail anggota</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup detail"></button></div>
            <div class="offcanvas-body" data-tree-detail></div>
        </div>
        <script type="application/json" id="tree-data">{!! Illuminate\Support\Js::encode($tree) !!}</script>
    @endif
</x-layouts.app>
