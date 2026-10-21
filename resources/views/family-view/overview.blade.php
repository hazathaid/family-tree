<x-layouts.scoped title="Silsilah {{ $view['family']['name'] }}">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-primary fw-semibold mb-1">{{ $view['family']['name'] }}</p>
            <h1 class="h3 mb-1">Silsilah Keluarga</h1>
            <p class="text-body-secondary mb-0">
                Akses baca-saja untuk {{ $view['anchor']['display_name'] }}.
                Berlaku sampai {{ $grant->expires_at->translatedFormat('d F Y H:i') }}.
            </p>
        </div>
        <a class="btn btn-primary" href="{{ route('family-view.tree', $view['family']['uuid']) }}">Buka bagan silsilah</a>
    </div>

    @if(session('status'))<x-alert variant="success">{{ session('status') }}</x-alert>@endif

    <x-alert variant="info">
        Anggota yang masih hidup disamarkan menjadi “Anggota Hidup”. Data ini tidak dapat diubah,
        diunduh massal, atau dibagikan dari halaman ini.
    </x-alert>

    <div class="row g-3 mb-4" aria-label="Statistik silsilah">
        @foreach(['Anggota' => $view['statistics']['members'], 'Generasi' => $view['statistics']['generations'], 'Hidup' => $view['statistics']['living'], 'Meninggal' => $view['statistics']['deceased']] as $label => $value)
            <div class="col-6 col-lg-3">
                <x-card>
                    <p class="text-body-secondary mb-1">{{ $label }}</p>
                    <p class="h3 mb-0">{{ $value }}</p>
                </x-card>
            </div>
        @endforeach
    </div>

    <x-card title="Anggota dalam cakupan akses">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th scope="col">Nama</th><th scope="col">Hubungan dengan acuan</th><th scope="col">Status</th></tr>
                </thead>
                <tbody>
                    @foreach($view['tree']['nodes'] as $node)
                        <tr>
                            <td>
                                @if($node['is_redacted'])
                                    <span class="text-body-secondary">Anggota Hidup</span>
                                @else
                                    {{ $node['display_name'] }}
                                @endif
                            </td>
                            <td>{{ $node['relationship_label'] ?? '—' }}</td>
                            <td>
                                @if($node['is_redacted'])
                                    <x-badge variant="secondary">Disamarkan</x-badge>
                                @elseif($node['is_alive'])
                                    <x-badge variant="success">Hidup</x-badge>
                                @else
                                    <x-badge variant="secondary">Meninggal</x-badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-body-secondary small mb-0">
            Label hubungan dihitung server-side relatif terhadap {{ $view['anchor']['display_name'] }}.
        </p>
    </x-card>
</x-layouts.scoped>
