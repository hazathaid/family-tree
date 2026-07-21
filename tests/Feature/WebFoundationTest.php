<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class WebFoundationTest extends TestCase
{
    public function test_public_foundation_page_uses_shared_responsive_layout(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Lewati ke konten utama')
            ->assertSee('aria-label="Navigasi utama"', false)
            ->assertSee('.css', false)
            ->assertSee('Sejarah keluarga dalam satu tempat');
    }

    public function test_shared_components_render_accessible_states(): void
    {
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['email' => ['Email wajib diisi.']]));
        $this->app['view']->share('errors', $errors);

        $html = Blade::render(<<<'BLADE'
            <x-form.input name="email" label="Email" required />
            <x-alert variant="danger">Gagal diproses.</x-alert>
            <x-empty-state title="Belum ada data" message="Tambahkan data pertama Anda." />
            <x-loading-state label="Memuat anggota" :lines="2" />
        BLADE);

        self::assertStringContainsString('is-invalid', $html);
        self::assertStringContainsString('aria-invalid="true"', $html);
        self::assertStringContainsString('Email wajib diisi.', $html);
        self::assertStringContainsString('role="alert"', $html);
        self::assertStringContainsString('Belum ada data', $html);
        self::assertStringContainsString('aria-label="Memuat anggota"', $html);
    }

    public function test_authenticated_layout_has_desktop_and_mobile_navigation(): void
    {
        $html = Blade::render('<x-layouts.app title="Dashboard"><h1>Dashboard keluarga</h1></x-layouts.app>');

        self::assertStringContainsString('app-sidebar', $html);
        self::assertStringContainsString('id="mobile-navigation"', $html);
        self::assertStringContainsString('aria-label="Buka menu navigasi"', $html);
        self::assertStringContainsString('Dashboard keluarga', $html);
    }
}
