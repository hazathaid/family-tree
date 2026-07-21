<x-layouts.app :title="'Moderasi '.$family->name">
    <div class="mb-4"><a href="{{ route('admin.families.index') }}">← Daftar keluarga</a><h1 class="h2 mt-2">{{ $family->name }}</h1><p class="text-secondary">{{ $family->description ?: 'Tidak ada deskripsi keluarga.' }}</p></div>
    @if(session('success'))<x-alert variant="success">{{ session('success') }}</x-alert>@endif
    @if($errors->any())<x-alert variant="danger">{{ $errors->first() }}</x-alert>@endif
    @foreach ([['Artikel', 'article', $family->articles, 'title'], ['Foto', 'photo', $family->photos, 'title'], ['Acara', 'event', $family->events, 'title']] as [$heading, $type, $items, $label])
        <section class="mb-4" aria-labelledby="section-{{ $type }}"><h2 class="h4" id="section-{{ $type }}">{{ $heading }}</h2><x-card :padding="false"><div class="list-group list-group-flush">
        @forelse($items as $item)<div class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-3"><span>{{ $item->{$label} ?: 'Tanpa judul' }}</span><form method="POST" action="{{ route('admin.families.content.destroy', $family) }}" onsubmit="return confirm('Hapus konten ini secara permanen dari tampilan?')">@csrf @method('DELETE')<input type="hidden" name="content_type" value="{{ $type }}"><input type="hidden" name="content_uuid" value="{{ $item->uuid }}"><input type="hidden" name="confirm" value="1"><button class="btn btn-sm btn-outline-danger" type="submit">Hapus konten</button></form></div>
        @empty<div class="list-group-item text-secondary">Tidak ada {{ strtolower($heading) }} untuk ditinjau.</div>@endforelse
        </div></x-card></section>
    @endforeach
</x-layouts.app>
