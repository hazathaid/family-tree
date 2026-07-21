<x-layouts.app title="Laporan & Kontribusi">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4"><div><h1 class="h2 mb-1">Laporan & kontribusi</h1><p class="text-secondary mb-0">Insight keluarga {{ $family->name }} dan apresiasi para kontributor.</p></div></div>
    <form method="GET" action="{{ route('reports.index') }}" class="row g-3 align-items-end mb-4" aria-label="Filter periode laporan">
        <div class="col-sm-4 col-lg-3"><x-form.input name="from" type="date" label="Dari tanggal" :value="$from" /></div>
        <div class="col-sm-4 col-lg-3"><x-form.input name="to" type="date" label="Sampai tanggal" :value="$to" /></div>
        <div class="col-sm-4"><x-button type="submit">Terapkan periode</x-button></div>
    </form>

    <div class="row g-3 mb-4">
        @foreach(['total_members' => ['Total anggota', $statistics['total_members']], 'total_generations' => ['Generasi', $statistics['total_generations']], 'active_users' => ['Pengguna aktif', $activityReport['active_users']], 'uploads' => ['Foto diunggah', $activityReport['uploads']['total']], 'articles' => ['Artikel dibuat', $activityReport['articles']['total']], 'points' => ['Poin Anda', $gamification['points']]] as $card)
            <div class="col-6 col-lg-2"><x-card class="h-100"><div class="h3 mb-1">{{ number_format($card[1]) }}</div><div class="small text-secondary">{{ $card[0] }}</div></x-card></div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        @foreach(['members_by_generation' => ['Anggota per generasi', collect($statistics['members_by_generation'])->map(fn($total, $label) => (object) compact('label', 'total'))], 'cities' => ['Anggota per kota', $insights['cities']], 'growth' => ['Pertumbuhan anggota', $insights['growth']], 'activity' => ['Tren aktivitas', $insights['activity']]] as $key => [$title, $rows])
            <section class="col-lg-6"><x-card :title="$title" class="h-100">
                @if(collect($rows)->isEmpty())<x-empty-state title="Belum ada data" message="Data akan tampil setelah keluarga memiliki aktivitas pada periode ini." />@else
                    @php($maximum = max(1, (int) collect($rows)->max('total')))
                    <div class="report-chart mb-3" role="img" aria-label="Visualisasi {{ $title }}">@foreach($rows as $row)<div class="report-bar-row"><span class="small text-truncate">{{ $row->label }}</span><span class="report-bar" style="--report-width: {{ round(((int) $row->total / $maximum) * 100) }}%"></span><strong class="small">{{ $row->total }}</strong></div>@endforeach</div>
                    <div class="table-responsive"><table class="table table-sm"><caption class="visually-hidden">Data {{ $title }}</caption><thead><tr><th>Kelompok</th><th class="text-end">Jumlah</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->label }}</td><td class="text-end">{{ $row->total }}</td></tr>@endforeach</tbody></table></div>
                @endif
            </x-card></section>
        @endforeach
    </div>

    <div class="row g-4">
        <section class="col-lg-5"><x-card title="Badge saya" class="h-100"><div class="d-flex flex-wrap gap-2">@forelse($gamification['badges'] as $award)<span class="badge text-bg-warning p-2" title="{{ $award->badge->description }}">🏅 {{ $award->badge->name }}</span>@empty<x-empty-state title="Belum ada badge" message="Tambah anggota, unggah foto, atau tulis artikel untuk meraih badge pertama." />@endforelse</div></x-card></section>
        <section class="col-lg-7"><x-card title="Papan peringkat keluarga" class="h-100"><div class="table-responsive"><table class="table align-middle"><caption class="visually-hidden">Peringkat kontributor {{ $family->name }}</caption><thead><tr><th>Peringkat</th><th>Kontributor</th><th class="text-end">Poin</th></tr></thead><tbody>@forelse($leaderboard as $row)<tr @if($row->uuid === auth()->user()->uuid) class="table-primary" @endif><td>#{{ $row->rank }}</td><td>{{ $row->name }} @if($row->uuid === auth()->user()->uuid)<span class="visually-hidden">(Anda)</span>@endif</td><td class="text-end fw-semibold">{{ $row->points }}</td></tr>@empty<tr><td colspan="3"><x-empty-state title="Belum ada peringkat" message="Kontribusi pertama akan memulai papan peringkat." /></td></tr>@endforelse</tbody></table></div></x-card></section>
    </div>
</x-layouts.app>
