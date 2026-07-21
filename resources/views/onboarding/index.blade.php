<x-layouts.app title="Pilih keluarga">
    <div class="container-fluid px-0" style="max-width: 64rem">
        <header class="mb-4">
            <p class="text-primary fw-semibold mb-1">Selamat datang</p>
            <h1 class="h2">Pilih atau buat keluarga</h1>
            <p class="text-secondary">Keluarga aktif menentukan data yang tampil di aplikasi.</p>
        </header>

        <div class="row g-4">
            <div class="col-lg-6">
                <x-card title="Keluarga Anda">
                    @if ($families->isEmpty())
                        <x-empty-state title="Belum ada keluarga" message="Buat keluarga pertama Anda melalui formulir di halaman ini." />
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($families as $family)
                                <form method="POST" action="{{ route('families.activate', $family) }}" class="list-group-item px-0 d-flex align-items-center justify-content-between gap-3">
                                    @csrf
                                    <span><strong class="d-block">{{ $family->name }}</strong><small class="text-secondary">{{ $family->origin_city ?: 'Lokasi asal belum diisi' }}</small></span>
                                    <x-button type="submit" variant="outline-primary" size="sm">Pilih</x-button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>
            <div class="col-lg-6">
                <x-card title="Buat keluarga baru">
                    <form method="POST" action="{{ route('onboarding.families.store') }}" class="vstack gap-3">
                        @csrf
                        <x-form.input name="name" label="Nama keluarga" required />
                        <x-form.input name="origin_city" label="Kota asal" />
                        <div>
                            <label class="form-label" for="description">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <x-button type="submit">Buat dan lanjutkan</x-button>
                    </form>
                </x-card>
            </div>
        </div>
    </div>
</x-layouts.app>
