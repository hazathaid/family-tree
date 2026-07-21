<x-layouts.app title="Dashboard {{ $family->name }}">
    @if (session('status'))
        <x-alert variant="success">{{ session('status') }}</x-alert>
    @endif

    <section class="dashboard-welcome mb-4" aria-labelledby="dashboard-title">
        <div>
            <p class="mb-1 text-white-50">Keluarga aktif</p>
            <h1 id="dashboard-title" class="h3 mb-2">Selamat datang, {{ auth()->user()->name }}</h1>
            <p class="mb-0">Lihat kabar terbaru dari {{ $family->name }} dalam satu tempat.</p>
        </div>
    </section>

    @php
        $statistics = [
            ['label' => 'Total anggota', 'value' => $dashboard->statistics->totalMembers, 'tone' => 'primary'],
            ['label' => 'Anggota hidup', 'value' => $dashboard->statistics->livingMembers, 'tone' => 'success'],
            ['label' => 'Anggota meninggal', 'value' => $dashboard->statistics->deceasedMembers, 'tone' => 'secondary'],
            ['label' => 'Artikel', 'value' => $dashboard->statistics->totalArticles, 'tone' => 'info'],
            ['label' => 'Foto', 'value' => $dashboard->statistics->totalPhotos, 'tone' => 'warning'],
            ['label' => 'Acara', 'value' => $dashboard->statistics->totalEvents, 'tone' => 'danger'],
        ];
    @endphp

    <section class="row g-3 mb-4" aria-label="Statistik keluarga">
        @foreach ($statistics as $statistic)
            <div class="col-6 col-lg-4 col-xl-2">
                <div class="card h-100 dashboard-stat border-0 border-start border-4 border-{{ $statistic['tone'] }}">
                    <div class="card-body">
                        <p class="small text-body-secondary mb-1">{{ $statistic['label'] }}</p>
                        <p class="h3 mb-0">{{ number_format($statistic['value']) }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <div class="row g-4">
        <div class="col-xl-8">
            <x-card title="Aktivitas keluarga terbaru" subtitle="Kabar terbaru dari anggota keluarga Anda.">
                @if ($dashboard->recentActivity->isEmpty())
                    <x-empty-state title="Belum ada aktivitas" message="Aktivitas anggota, artikel, foto, dan acara akan tampil di sini." />
                @else
                    <div class="dashboard-list">
                        @foreach ($dashboard->recentActivity as $activity)
                            @php
                                $activityLabels = [
                                    'MEMBER_CREATED' => 'Anggota baru ditambahkan',
                                    'ARTICLE_CREATED' => 'Artikel baru dibuat',
                                    'PHOTO_UPLOADED' => 'Foto baru diunggah',
                                    'EVENT_CREATED' => 'Acara baru dibuat',
                                ];
                                $subject = $activity->payload['name'] ?? $activity->payload['title'] ?? null;
                            @endphp
                            <article class="dashboard-list-item">
                                <span class="dashboard-dot" aria-hidden="true"></span>
                                <div class="flex-grow-1">
                                    <p class="mb-1 fw-semibold">{{ $activityLabels[$activity->activity_type] ?? 'Aktivitas keluarga' }}</p>
                                    @if ($subject)<p class="mb-1 text-body-secondary">{{ $subject }}</p>@endif
                                    <small class="text-body-secondary">
                                        {{ $activity->user?->name ?? 'Sistem' }} · {{ $activity->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <div class="mt-4">
                <x-card title="Anggota yang baru ditambahkan">
                    @if ($dashboard->recentMembers->isEmpty())
                        <x-empty-state title="Belum ada anggota" message="Anggota keluarga yang baru ditambahkan akan tampil di sini." />
                    @else
                        <div class="row g-3">
                            @foreach ($dashboard->recentMembers as $member)
                                <div class="col-sm-6 col-lg-4">
                                    <div class="d-flex align-items-center gap-3 rounded border p-3 h-100">
                                        <span class="dashboard-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($member->full_name, 0, 1)) }}</span>
                                        <div class="min-width-0">
                                            <p class="mb-0 fw-semibold text-truncate">{{ $member->is_alive ? '' : '† ' }}{{ $member->full_name }}</p>
                                            <small class="text-body-secondary">Ditambahkan {{ $member->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>
        </div>

        <aside class="col-xl-4" aria-label="Ringkasan keluarga">
            <div class="d-grid gap-4">
                <x-card title="Ulang tahun mendatang" subtitle="Hari ini hingga tujuh hari ke depan.">
                    @if ($dashboard->upcomingBirthdays->isEmpty())
                        <x-empty-state title="Tidak ada ulang tahun" message="Belum ada ulang tahun keluarga dalam tujuh hari ke depan." />
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($dashboard->upcomingBirthdays as $member)
                                <li class="list-group-item px-0 d-flex justify-content-between gap-3">
                                    <span>{{ $member->full_name }}</span>
                                    <small class="text-body-secondary text-nowrap">{{ \Illuminate\Support\Carbon::parse($member->next_birthday)->translatedFormat('d M') }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>

                <x-card title="Acara mendatang">
                    @if ($dashboard->upcomingEvents->isEmpty())
                        <x-empty-state title="Belum ada acara" message="Acara keluarga mendatang akan tampil di sini." />
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($dashboard->upcomingEvents as $event)
                                <li class="list-group-item px-0">
                                    <p class="mb-1 fw-semibold">{{ $event->title }}</p>
                                    <small class="text-body-secondary">{{ $event->event_date->translatedFormat('d M Y, H:i') }}{{ $event->location ? ' · '.$event->location : '' }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>

                <x-card title="Notifikasi" subtitle="{{ $dashboard->unreadNotifications }} belum dibaca">
                    @if ($dashboard->notifications->isEmpty())
                        <x-empty-state title="Tidak ada notifikasi" message="Notifikasi keluarga Anda akan tampil di sini." />
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($dashboard->notifications as $notification)
                                <li class="list-group-item px-0 {{ $notification->is_read ? '' : 'fw-semibold' }}">
                                    <p class="mb-1">{{ $notification->title }}</p>
                                    <small class="text-body-secondary">{{ $notification->created_at->diffForHumans() }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>

                <x-card title="Fakta keluarga">
                    @if ($dashboard->facts === [])
                        <x-empty-state title="Fakta belum tersedia" message="Lengkapi asal keluarga dan tanggal lahir anggota untuk melihat fakta keluarga." />
                    @else
                        <dl class="mb-0">
                            @foreach ($dashboard->facts as $fact)
                                <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                    <dt class="fw-normal text-body-secondary">{{ $fact['label'] }}</dt>
                                    <dd class="mb-0 fw-semibold text-end">{{ $fact['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </x-card>
            </div>
        </aside>
    </div>
</x-layouts.app>
