<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberDocument;
use App\Models\User;
use App\Services\MemberDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberDocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_and_deletes_a_document(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);
        $member = FamilyMember::factory()->create(['family_id' => $family->id, 'created_by' => $user->id]);
        $file = UploadedFile::fake()->create('akta.pdf', 100, 'application/pdf');

        $service = app(MemberDocumentService::class);
        $document = $service->store($user, $member, [
            'title' => 'Akta Lahir',
            'category' => MemberDocument::CATEGORY_LEGAL,
            'document_date' => '2020-01-01',
        ], $file);

        $this->assertDatabaseHas('member_documents', [
            'id' => $document->id,
            'family_member_id' => $member->id,
            'uploaded_by' => $user->id,
            'title' => 'Akta Lahir',
        ]);
        Storage::disk('public')->assertExists($document->path);
        $this->assertDatabaseHas('activity_logs', ['activity_type' => ActivityLog::MEMBER_DOCUMENT_UPLOADED]);

        $service->delete($user, $document);

        $this->assertSoftDeleted('member_documents', ['id' => $document->id]);
        Storage::disk('public')->assertMissing($document->path);
        $this->assertDatabaseHas('activity_logs', ['activity_type' => ActivityLog::MEMBER_DOCUMENT_DELETED]);
    }
}
