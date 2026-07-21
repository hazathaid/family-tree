<x-layouts.app title="Pengaturan Keluarga">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h2 mb-1">Pengaturan {{ $family->name }}</h1><p class="text-secondary mb-0">Kelola profil, cabang, akses, dan preferensi keluarga.</p></div>
    </div>
    @if(session('status'))<x-alert variant="success">{{ session('status') }}</x-alert>@endif

    <ul class="nav nav-tabs mb-4" role="tablist">
        @foreach (['profile' => 'Profil', 'branches' => 'Cabang', 'access' => 'Anggota & Peran', 'privacy' => 'Privasi', 'notifications' => 'Notifikasi'] as $id => $label)
            <li class="nav-item" role="presentation"><button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#{{ $id }}" type="button" role="tab">{{ $label }}</button></li>
        @endforeach
    </ul>

    <div class="tab-content">
        <section class="tab-pane fade show active" id="profile" role="tabpanel">
            <x-card title="Profil keluarga">
                @if($family->cover_image)<img src="{{ Storage::url($family->cover_image) }}" class="family-cover mb-3" alt="Sampul {{ $family->name }}">@endif
                <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf @method('PUT')
                    <div class="col-md-6"><x-form.input name="name" label="Nama keluarga" :value="old('name', $family->name)" required /></div>
                    <div class="col-md-6"><x-form.input name="origin_city" label="Kota asal" :value="old('origin_city', $family->origin_city)" /></div>
                    <div class="col-12"><label class="form-label" for="description">Deskripsi</label><textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $family->description) }}</textarea></div>
                    <div class="col-md-6"><label class="form-label" for="logo">Logo (maks. 5 MB)</label><input class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp"></div>
                    <div class="col-md-6"><label class="form-label" for="cover_image">Sampul (maks. 10 MB)</label><input class="form-control @error('cover_image') is-invalid @enderror" id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp"></div>
                    @can('update', $family)<div class="col-12"><x-button type="submit">Simpan profil</x-button></div>@endcan
                </form>
            </x-card>
        </section>

        <section class="tab-pane fade" id="branches" role="tabpanel">
            <div class="row g-4">
                @can('manageBranches', $family)<div class="col-lg-4"><x-card title="Tambah cabang"><form method="POST" action="{{ route('settings.branches.store') }}" class="vstack gap-3">@csrf<x-form.input name="name" label="Nama cabang" required /><x-form.input name="description" label="Deskripsi" /><x-button type="submit">Tambah cabang</x-button></form></x-card></div>@endcan
                <div class="col-lg-8"><x-card title="Daftar cabang">
                    @forelse($branches as $branch)
                        <div class="border-bottom py-3 first-pt-0"><form method="POST" action="{{ route('settings.branches.update', $branch) }}" class="row g-2 align-items-end">@csrf @method('PUT')<div class="col-md-4"><label class="form-label" for="branch-name-{{ $branch->uuid }}">Nama</label><input class="form-control" id="branch-name-{{ $branch->uuid }}" name="name" value="{{ $branch->name }}" required></div><div class="col-md-5"><label class="form-label" for="branch-description-{{ $branch->uuid }}">Deskripsi</label><input class="form-control" id="branch-description-{{ $branch->uuid }}" name="description" value="{{ $branch->description }}"></div>@can('update', $branch)<div class="col-md-3"><button class="btn btn-outline-primary" type="submit">Simpan</button></div>@endcan</form>
                        @can('delete', $branch)<form method="POST" action="{{ route('settings.branches.destroy', $branch) }}" class="mt-2" onsubmit="return confirm('Hapus cabang ini? Anggota tidak akan ikut terhapus.')">@csrf @method('DELETE')<input type="hidden" name="confirm" value="1"><button class="btn btn-sm btn-link text-danger px-0" type="submit">Hapus cabang</button></form>@endcan</div>
                    @empty <x-empty-state title="Belum ada cabang" message="Tambahkan cabang untuk mengelompokkan anggota keluarga." /> @endforelse
                    {{ $branches->links() }}
                </x-card></div>
            </div>
        </section>

        <section class="tab-pane fade" id="access" role="tabpanel">
            <div class="row g-4">
                @can('manageRoles', $family)<div class="col-lg-4"><x-card title="Undang pengguna"><form method="POST" action="{{ route('settings.members.invite') }}" class="vstack gap-3">@csrf<x-form.input name="email" type="email" label="Email pengguna terdaftar" required /><x-form.select name="role" label="Peran" :options="['member' => 'Member', 'admin' => 'Admin', 'owner' => 'Owner']" /><x-button type="submit">Tambahkan akses</x-button></form></x-card></div>@endcan
                <div class="col-lg-8"><x-card title="Akses keluarga"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Pengguna</th><th>Peran</th><th>Aksi</th></tr></thead><tbody>@foreach($memberships as $membership)<tr><td>{{ $membership->user->name }}<div class="small text-secondary">{{ $membership->user->email }}</div></td><td><x-badge>{{ ucfirst($membership->role) }}</x-badge></td><td>@can('manageRoles', $family)<form method="POST" action="{{ route('settings.members.role', $membership) }}" class="d-flex gap-2">@csrf @method('PATCH')<select class="form-select form-select-sm" name="role" aria-label="Peran {{ $membership->user->name }}">@foreach(['member','admin','owner'] as $role)<option value="{{ $role }}" @selected($membership->role === $role)>{{ ucfirst($role) }}</option>@endforeach</select><button class="btn btn-sm btn-outline-primary">Simpan</button></form><form method="POST" action="{{ route('settings.members.destroy', $membership) }}" class="mt-1" onsubmit="return confirm('Cabut akses pengguna ini?')">@csrf @method('DELETE')<input type="hidden" name="confirm" value="1"><button class="btn btn-sm btn-link text-danger px-0">Cabut akses</button></form>@endcan</td></tr>@endforeach</tbody></table></div></x-card></div>
            </div>
        </section>

        <section class="tab-pane fade" id="privacy" role="tabpanel"><x-card title="Privasi keluarga"><p class="mb-2">Data keluarga hanya dapat dilihat oleh pengguna yang memiliki peran aktif pada keluarga ini.</p><p class="text-secondary mb-0">Perubahan profil, cabang, dan anggota dilindungi policy Owner/Admin. Penghapusan selalu membutuhkan konfirmasi.</p></x-card></section>
        <section class="tab-pane fade" id="notifications" role="tabpanel"><x-card title="Notifikasi keluarga"><p class="mb-2">Notifikasi keluarga dikirim kepada pengguna sesuai akun dan event yang relevan.</p><p class="text-secondary mb-0">Preferensi notifikasi pribadi akan dikelola dari halaman profil pengguna.</p></x-card></section>
    </div>
</x-layouts.app>
