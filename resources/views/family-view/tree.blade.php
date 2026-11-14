<x-layouts.scoped title="Bagan {{ $view['family']['name'] }}">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <p class="text-primary fw-semibold mb-1">{{ $view['family']['name'] }}</p>
            <h1 class="h3 mb-1">Bagan Silsilah Baca-Saja</h1>
            <p class="text-body-secondary mb-0">Relatif terhadap {{ $view['anchor']['display_name'] }}. Anggota hidup disamarkan.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('family-view.show', $view['family']['uuid']) }}">Kembali ke ringkasan</a>
    </div>

    <x-card class="mb-3">
        <form method="GET" action="{{ route('family-view.tree', $view['family']['uuid']) }}" class="row g-3 align-items-end" aria-label="Pengaturan bagan">
            <div class="col-6 col-lg-3">
                <label class="form-label" for="depth">Kedalaman</label>
                <select class="form-select" id="depth" name="depth">
                    @foreach([1, 2, 3, 4, 5, 10, 20] as $depth)
                        <option value="{{ $depth }}" @selected($filters['depth'] === $depth)>{{ $depth }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label" for="layout">Tata letak</label>
                <select class="form-select" id="layout" name="layout">
                    <option value="vertical" @selected($filters['layout'] === 'vertical')>Vertikal</option>
                    <option value="horizontal" @selected($filters['layout'] === 'horizontal')>Horizontal</option>
                    <option value="radial" @selected($filters['layout'] === 'radial')>Radial</option>
                    <option value="compact" @selected($filters['layout'] === 'compact')>Ringkas</option>
                </select>
            </div>
            <div class="col-lg-3 d-grid"><button class="btn btn-primary" type="submit">Tampilkan</button></div>
        </form>
    </x-card>

    <x-alert variant="info">Bagan ini tidak dapat diedit, ditambah anggota, atau diekspor. Gunakan daftar di bawah untuk pembaca layar.</x-alert>

    @php($nodesByUuid = collect($view['tree']['nodes'])->keyBy('uuid'))
    <div class="tree-viewer mb-3" style="overflow:auto;cursor:default" aria-hidden="true">
        <div class="tree-stage" style="position:relative;width:{{ $view['tree']['viewport']['width'] }}px;height:{{ $view['tree']['viewport']['height'] }}px">
            <svg class="tree-edges" width="{{ $view['tree']['viewport']['width'] }}" height="{{ $view['tree']['viewport']['height'] }}" aria-hidden="true">
                @foreach($view['tree']['edges'] as $edge)
                    @php($from = $nodesByUuid->get($edge['source_uuid']))
                    @php($to = $nodesByUuid->get($edge['target_uuid']))
                    @if($from && $to)
                        <line class="tree-edge {{ ($edge['is_ended'] ?? false) ? 'tree-edge-ended' : '' }}" x1="{{ $from['position']['x'] }}" y1="{{ $from['position']['y'] }}" x2="{{ $to['position']['x'] }}" y2="{{ $to['position']['y'] }}" />
                    @endif
                @endforeach
            </svg>
            @foreach($view['tree']['nodes'] as $node)
                <article class="tree-node {{ $node['is_root'] ? 'tree-node-root' : '' }} {{ ! $node['is_alive'] ? 'tree-node-deceased' : '' }}"
                    style="left:{{ $node['position']['x'] }}px;top:{{ $node['position']['y'] }}px">
                    <div class="d-flex align-items-start gap-2 p-2">
                        @if($node['profile_photo_url'])
                            <img class="tree-node-photo" src="{{ $node['profile_photo_url'] }}" alt="">
                        @else
                            <span class="tree-node-photo tree-node-photo-placeholder" aria-hidden="true">{{ $node['is_redacted'] ? '?' : mb_substr($node['display_name'], 0, 1) }}</span>
                        @endif
                        <div class="min-w-0">
                            <strong class="d-block text-truncate">{{ $node['display_name'] }}</strong>
                            <span class="d-block small text-body-secondary">{{ $node['relationship_label'] ?? '—' }}</span>
                            @if($node['is_redacted'])
                                <x-badge variant="secondary">Disamarkan</x-badge>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>

    <x-card title="Daftar anggota (aksesibel)">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th scope="col">Nama</th><th scope="col">Hubungan</th><th scope="col">Generasi</th><th scope="col">Status</th></tr></thead>
                <tbody>
                    @foreach($view['tree']['nodes'] as $node)
                        <tr>
                            <td>{{ $node['is_redacted'] ? 'Anggota Hidup' : $node['display_name'] }}</td>
                            <td>{{ $node['relationship_label'] ?? '—' }}</td>
                            <td>{{ $node['generation'] }}</td>
                            <td>{{ $node['is_redacted'] ? 'Disamarkan' : ($node['is_alive'] ? 'Hidup' : 'Meninggal') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts.scoped>
