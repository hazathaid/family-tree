<x-layouts.guest title="Terima Undangan">
    <h1 class="h3 mb-2">Aktifkan akun anggota</h1>
    <p class="text-secondary">Anda diundang sebagai <strong>{{ $invitation->member->full_name }}</strong> di keluarga {{ $invitation->member->family->name }}.</p>
    @if($errors->any())<x-alert variant="danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>@endif
    <form method="POST" action="{{ route('member-account-invitations.accept', $token) }}" class="vstack gap-3">
        @csrf
        <x-form.input name="email" type="email" label="Email" :value="$invitation->email" disabled />
        <x-form.input name="name" label="Nama akun" :value="old('name', $invitation->member->full_name)" required />
        <x-form.input name="password" type="password" label="Password" required />
        <x-form.input name="password_confirmation" type="password" label="Konfirmasi password" required />
        <button class="btn btn-primary" type="submit">Buat akun dan hubungkan profil</button>
    </form>
</x-layouts.guest>
