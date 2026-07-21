<x-layouts.app title="Notifikasi">
    <div class="d-flex justify-content-between">
        <div><h1>Notifikasi</h1><p class="text-secondary">{{ $unreadCount }} belum dibaca.</p></div>
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-outline-primary">Tandai semua dibaca</button></form>
    </div>
    <div class="my-3"><a href="{{ route('notifications.index') }}">Semua</a> · <a href="{{ route('notifications.index', ['status' => 'unread']) }}">Belum dibaca</a></div>
    @if($notifications->isEmpty())
        <x-empty-state title="Tidak ada notifikasi" message="Pembaruan penting akan tampil di sini." />
    @else
        @foreach($notifications as $notification)
            <div class="card card-body mb-3 {{ $notification->is_read ? '' : 'border-primary' }}">
                <div class="d-flex justify-content-between"><div><strong>{{ $notification->title }}</strong><p class="mb-0">{{ $notification->body }}</p></div>
                @unless($notification->is_read)<form method="POST" action="{{ route('notifications.read', $notification->uuid) }}">@csrf<button class="btn btn-sm btn-outline-primary">Tandai dibaca</button></form>@endunless</div>
            </div>
        @endforeach
        {{ $notifications->links() }}
    @endif
</x-layouts.app>
