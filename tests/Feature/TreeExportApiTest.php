<?php

namespace Tests\Feature;

use App\Jobs\GenerateTreeExport;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\TreeExport;
use App\Models\User;
use App\Services\TreeExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TreeExportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_an_export_queues_a_job(): void
    {
        Queue::fake();
        [$user, $family] = $this->familyUser();
        $root = $this->member($family, $user);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/tree/exports', [
            'member_uuid' => $root->uuid,
            'format' => 'pdf',
            'mode' => 'full',
            'depth' => 3,
            'layout' => 'vertical',
        ])->assertStatus(202)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.format', 'pdf');

        Queue::assertPushed(GenerateTreeExport::class);
    }

    public function test_a_completed_export_can_be_downloaded(): void
    {
        Storage::fake('local');
        [$user, $family] = $this->familyUser();
        $root = $this->member($family, $user);

        $export = app(TreeExportService::class)->request($user, $root, 'full', 3, 'vertical', 'pdf');
        app(TreeExportService::class)->run($export);

        $this->assertSame(TreeExport::STATUS_COMPLETED, $export->refresh()->status);
        Storage::disk('local')->assertExists($export->path);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/tree/exports/'.$export->uuid)
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.download_url', url('/api/v1/tree/exports/'.$export->uuid.'/download'));

        $this->get('/api/v1/tree/exports/'.$export->uuid.'/download')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_png_export_renders_an_image(): void
    {
        Storage::fake('local');
        [$user, $family] = $this->familyUser();
        $root = $this->member($family, $user);

        $export = app(TreeExportService::class)->request($user, $root, 'full', 2, 'vertical', 'png');
        app(TreeExportService::class)->run($export);

        Sanctum::actingAs($user);

        $response = $this->get('/api/v1/tree/exports/'.$export->uuid.'/download')->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }

    public function test_outsiders_cannot_export_or_download(): void
    {
        Storage::fake('local');
        [$user, $family] = $this->familyUser();
        $root = $this->member($family, $user);
        $export = app(TreeExportService::class)->request($user, $root, 'full', 2, 'vertical', 'pdf');
        app(TreeExportService::class)->run($export);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/tree/exports', ['member_uuid' => $root->uuid, 'format' => 'pdf'])->assertForbidden();
        $this->getJson('/api/v1/tree/exports/'.$export->uuid)->assertForbidden();
        $this->get('/api/v1/tree/exports/'.$export->uuid.'/download')->assertForbidden();
    }

    public function test_prune_removes_expired_exports(): void
    {
        Storage::fake('local');
        [$user, $family] = $this->familyUser();
        $root = $this->member($family, $user);
        $export = app(TreeExportService::class)->request($user, $root, 'full', 2, 'vertical', 'pdf');
        app(TreeExportService::class)->run($export);
        $path = $export->path;

        $export->update(['expires_at' => now()->subDay()]);

        $this->artisan('tree-exports:prune')->assertExitCode(0);

        $this->assertDatabaseMissing('tree_exports', ['id' => $export->id]);
        Storage::disk('local')->assertMissing($path);
    }

    /**
     * @return array{0: User, 1: Family}
     */
    private function familyUser(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        FamilyUserRole::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => FamilyUserRole::ROLE_OWNER,
        ]);

        return [$user, $family];
    }

    private function member(Family $family, User $user): FamilyMember
    {
        return FamilyMember::factory()->create([
            'family_id' => $family->id,
            'created_by' => $user->id,
            'full_name' => 'Budi',
        ]);
    }
}
