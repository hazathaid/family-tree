<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_configuration_uses_indonesian_as_primary_locale(): void
    {
        $this->assertSame('id', config('app.locale'));
    }

    public function test_api_validation_errors_are_returned_in_indonesian(): void
    {
        $response = $this->postJson('/api/v1/auth/register', ['email' => 'not-an-email']);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false);

        $errors = $response->json('errors');

        $this->assertStringContainsString('wajib diisi', strtolower((string) ($errors['name'][0] ?? '')));
        $this->assertStringContainsString('wajib diisi', strtolower((string) ($errors['password'][0] ?? '')));
        $this->assertStringContainsString('email yang valid', strtolower((string) ($errors['email'][0] ?? '')));
    }

    public function test_custom_attributes_are_translated(): void
    {
        $message = trans('validation.required', ['attribute' => trans('validation.attributes.full_name')]);

        $this->assertSame('Kolom nama lengkap wajib diisi.', $message);
    }
}
