<x-layouts.app title="Pencarian Keluarga">
    <div class="mb-4"><h1 class="h2 mb-1">Pencarian keluarga</h1><p class="text-secondary mb-0">Temukan anggota, artikel, dan acara di {{ $family->name }}.</p></div>
    <x-card title="Filter pencarian" class="mb-4">
        <form method="GET" action="{{ route('search.index') }}" class="row g-3" role="search">
            <div class="col-lg-4"><x-form.input name="keyword" label="Kata kunci" :value="$filters['keyword'] ?? ''" maxlength="100" /></div>
            <div class="col-md-4 col-lg-2"><x-form.input name="name" label="Nama anggota" :value="$filters['name'] ?? ''" /></div>
            <div class="col-md-4 col-lg-2"><x-form.input name="city" label="Kota" :value="$filters['city'] ?? ''" /></div>
            <div class="col-md-4 col-lg-2"><x-form.select name="status" label="Status" :selected="$filters['status'] ?? ''" :options="['' => 'Semua', 'alive' => 'Hidup', 'deceased' => 'Meninggal']" /></div>
            <div class="col-md-6"><x-form.select name="root_member_uuid" label="Akar generasi" :selected="$filters['root_member_uuid'] ?? ''" :options="['' => 'Pilih anggota'] + $roots->getCollection()->pluck('full_name', 'uuid')->all()" /></div>
            <div class="col-md-3"><x-form.input name="generation" type="number" label="Generasi (-100 sampai 100)" :value="$filters['generation'] ?? ''" min="-100" max="100" /></div>
            <div class="col-md-3 d-flex align-items-end gap-2"><x-button type="submit">Cari</x-button><a href="{{ route('search.index') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </x-card>

    @php($hasFilters = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty())
    @if(!$hasFilters)
        <x-empty-state icon="⌕" title="Mulai pencarian" message="Masukkan kata kunci atau gunakan filter untuk menemukan informasi keluarga." />
    @else
        <div class="row g-4">
            <section class="col-12" aria-labelledby="member-results"><x-card><h2 id="member-results" class="h4">Anggota <x-badge>{{ $members->total() }}</x-badge></h2>
                <div class="row g-3">@forelse($members as $member)<div class="col-md-6 col-xl-4"><a class="card h-100 text-decoration-none" href="{{ route('members.show', $member) }}"><div class="card-body"><strong>{{ $member->is_alive ? '' : '† ' }}{{ $member->full_name }}</strong><div class="small text-secondary">{{ $member->nickname ?: 'Tanpa nama panggilan' }} · {{ $member->birth_place ?: 'Kota belum diisi' }}</div></div></a></div>@empty<div class="col-12"><x-empty-state title="Anggota tidak ditemukan" message="Coba nama, kota, atau status yang berbeda." /></div>@endforelse</div>{{ $members->links() }}
            </x-card></section>
            <section class="col-lg-6" aria-labelledby="article-results"><x-card><h2 id="article-results" class="h4">Artikel <x-badge>{{ $articles->total() }}</x-badge></h2>
                @forelse($articles as $article)<a class="d-block border-bottom py-3 text-decoration-none" href="{{ route('articles.show', $article) }}"><strong>{{ $article->title }}</strong><div class="small text-secondary">{{ $article->category?->name }} · {{ $article->published_at?->translatedFormat('d M Y') }}</div></a>@empty<x-empty-state title="Artikel tidak ditemukan" message="Coba kata kunci yang lebih umum." />@endforelse{{ $articles->links() }}
            </x-card></section>
            <section class="col-lg-6" aria-labelledby="event-results"><x-card><h2 id="event-results" class="h4">Acara <x-badge>{{ $events->total() }}</x-badge></h2>
                @forelse($events as $event)<a class="d-block border-bottom py-3 text-decoration-none" href="{{ route('events.show', $event) }}"><strong>{{ $event->title }}</strong><div class="small text-secondary">{{ $event->event_date->timezone(config('app.timezone'))->translatedFormat('d M Y H:i') }} · {{ $event->location }}</div></a>@empty<x-empty-state title="Acara tidak ditemukan" message="Coba lokasi atau judul acara lain." />@endforelse{{ $events->links() }}
            </x-card></section>
        </div>
    @endif
</x-layouts.app>
