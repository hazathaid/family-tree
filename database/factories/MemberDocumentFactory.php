<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<MemberDocument> */
class MemberDocumentFactory extends Factory
{
    protected $model = MemberDocument::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'family_id' => Family::factory(),
            'family_member_id' => FamilyMember::factory(),
            'uploaded_by' => User::factory(),
            'title' => fake()->sentence(3),
            'category' => MemberDocument::CATEGORY_IDENTITY,
            'path' => 'member-documents/'.Str::uuid().'.pdf',
            'original_name' => 'dokumen.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'document_date' => now()->toDateString(),
            'notes' => null,
        ];
    }
}
