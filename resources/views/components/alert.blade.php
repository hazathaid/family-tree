@props(['variant' => 'info', 'dismissible' => false])
<div {{ $attributes->class(['alert', 'alert-'.$variant, 'alert-dismissible fade show' => $dismissible])->merge(['role' => 'alert']) }}>
    {{ $slot }}
    @if($dismissible)<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup pesan"></button>@endif
</div>
