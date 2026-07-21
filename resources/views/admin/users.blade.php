<x-layouts.app title="Kelola Pengguna">
    <div class="mb-4"><a href="{{ route('admin.dashboard') }}">← Administrasi</a><h1 class="h2 mt-2">Kelola pengguna</h1></div>
    @if(session('success'))<x-alert variant="success">{{ session('success') }}</x-alert>@endif
    @if($errors->any())<x-alert variant="danger">{{ $errors->first() }}</x-alert>@endif
    <x-card :padding="false">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><caption class="visually-hidden">Daftar pengguna platform</caption><thead><tr><th>Pengguna</th><th>Status</th><th>Bergabung</th><th><span class="visually-hidden">Tindakan</span></th></tr></thead><tbody>
        @forelse($users as $user)<tr><td><strong>{{ $user->name }}</strong><br><small class="text-secondary">{{ $user->email }}</small></td><td><x-badge :variant="$user->status === 'active' ? 'success' : 'secondary'">{{ ucfirst($user->status) }}</x-badge></td><td>{{ $user->created_at?->translatedFormat('d M Y') }}</td><td class="text-end"><form method="POST" action="{{ route('admin.users.update', $user) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $user->status === 'active' ? 'suspended' : 'active' }}"><button class="btn btn-sm {{ $user->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}" type="submit">{{ $user->status === 'active' ? 'Tangguhkan' : 'Aktifkan' }}</button></form></td></tr>
        @empty<tr><td colspan="4"><x-empty-state title="Belum ada pengguna" message="Pengguna yang terdaftar akan tampil di sini." /></td></tr>@endforelse
        </tbody></table></div>
    </x-card>
    <div class="mt-3"><x-pagination :paginator="$users" /></div>
</x-layouts.app>
