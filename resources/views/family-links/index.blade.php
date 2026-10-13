<x-layouts.app title="Tautan Keluarga">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-primary fw-semibold mb-1">{{ $family->name }}</p>
            <h1 class="h3 mb-1">Tautan Keluarga</h1>
            <p class="text-body-secondary mb-0">Tautkan pasangan yang datanya berada di keluarga lain tanpa menduplikasi anggota. Tautan aktif bersifat baca-saja dan memerlukan persetujuan kedua keluarga.</p>
        </div>
    </div>

    @if(session('status'))<x-alert variant="success">{{ session('status') }}</x-alert>@endif
    @if($errors->any())<x-alert variant="danger">{{ $errors->first() }}</x-alert>@endif

    <div class="row g-4">
        <div class="col-lg-5">
            <x-card title="Ajukan tautan pasangan">
                @if($member)
                    <p class="mb-3">Menautkan <strong>{{ $member->full_name }}</strong> ({{ $family->name }}) dengan pasangan di keluarga lain.</p>
                    <form method="GET" action="{{ route('family-links.index') }}" class="vstack gap-3 mb-3" aria-label="Cari pasangan">
                        <input type="hidden" name="member" value="{{ $member->uuid }}">
                        <x-form.input name="link_search" label="Cari pasangan (nama anggota di keluarga lain yang Anda kelola)" :value="$searchTerm" />
                        <x-button type="submit" variant="outline-primary">Cari pasangan</x-button>
                    </form>
                @else
                    <div class="alert alert-info">Pilih anggota keluarga Anda yang akan ditautkan, lalu cari pasangannya.</div>
                    <form method="GET" action="{{ route('family-links.index') }}" class="vstack gap-3" aria-label="Pilih anggota">
                        <div>
                            <label class="form-label" for="link-member">Anggota {{ $family->name }}</label>
                            <select class="form-select" id="link-member" name="member" required>
                                <option value="">Pilih anggota</option>
                                @foreach($memberOptions as $option)
                                    <option value="{{ $option->uuid }}">{{ $option->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-form.input name="link_search" label="Cari pasangan" :value="$searchTerm" />
                        <x-button type="submit" variant="outline-primary">Lanjut</x-button>
                    </form>
                @endif

                @if($member && $searchTerm !== '')
                    <hr>
                    @if($targets->isEmpty())
                        <p class="text-body-secondary mb-0">Tidak ada anggota yang cocok di keluarga lain yang dapat Anda akses.</p>
                    @else
                        <form method="POST" action="{{ route('family-links.store') }}" class="vstack gap-3">
                            @csrf
                            <input type="hidden" name="member_uuid" value="{{ $member->uuid }}">
                            <fieldset class="vstack gap-2">
                                <legend class="form-label float-none">Pilih pasangan</legend>
                                @foreach($targets as $target)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="linked_member_uuid" id="target-{{ $target->uuid }}" value="{{ $target->uuid }}" required>
                                        <label class="form-check-label" for="target-{{ $target->uuid }}">
                                            {{ $target->full_name }}
                                            <span class="text-body-secondary small d-block">{{ $target->family->name }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </fieldset>
                            <x-button type="submit">Kirim permintaan tautan</x-button>
                        </form>
                    @endif
                @endif
            </x-card>
        </div>

        <div class="col-lg-7">
            <x-card title="Daftar tautan keluarga">
                @forelse($links as $link)
                    @php
                        $involvesActive = $link->family_id === $family->id;
                        $localMember = $involvesActive ? $link->member : $link->linkedMember;
                        $remoteMember = $involvesActive ? $link->linkedMember : $link->member;
                        $remoteFamily = $involvesActive ? $link->linkedFamily : $link->family;
                        $statusVariant = match ($link->status) {
                            \App\Models\FamilyLink::STATUS_ACTIVE => 'success',
                            \App\Models\FamilyLink::STATUS_PENDING => 'warning',
                            default => 'secondary',
                        };
                    @endphp
                    <div class="border-bottom py-3 first-pt-0 d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <x-badge :variant="$statusVariant">{{ ucfirst($link->status) }}</x-badge>
                                <span class="text-body-secondary small">{{ $remoteFamily?->name }}</span>
                            </div>
                            <p class="mb-0"><strong>{{ $localMember?->full_name }}</strong> &harr; <strong>{{ $remoteMember?->full_name }}</strong></p>
                        </div>
                        <div class="d-flex flex-wrap align-items-start gap-2">
                            @if($link->isActive())
                                <x-button :href="route('family-links.partner-tree', $link)" variant="outline-primary" size="sm">Lihat silsilah pasangan</x-button>
                            @endif
                            @can('approve', $link)
                                @if($link->isPending())
                                    <form method="POST" action="{{ route('family-links.approve', $link) }}">
                                        @csrf
                                        <x-button type="submit" size="sm">Setujui</x-button>
                                    </form>
                                @endif
                            @endcan
                            @can('revoke', $link)
                                @if(! in_array($link->status, [\App\Models\FamilyLink::STATUS_REVOKED, \App\Models\FamilyLink::STATUS_EXPIRED], true))
                                    <form method="POST" action="{{ route('family-links.revoke', $link) }}" onsubmit="return confirm('Cabut tautan keluarga ini? Akses baca-saja akan dihentikan.')">
                                        @csrf
                                        <x-button type="submit" variant="outline-secondary" size="sm">Cabut</x-button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                @empty
                    <x-empty-state title="Belum ada tautan" message="Tautan keluarga yang Anda ajukan atau terima akan tampil di sini." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-layouts.app>
