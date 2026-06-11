<?php

namespace Tests\Unit;

use App\DTOs\FamilyBranchData;
use App\Models\Family;
use App\Models\FamilyBranch;
use App\Models\User;
use App\Repositories\Eloquent\EloquentFamilyBranchRepository;
use App\Services\FamilyBranchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FamilyBranchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_branch_for_family(): void
    {
        $family = Family::factory()->create(['created_by' => User::factory()]);
        $service = new FamilyBranchService(new EloquentFamilyBranchRepository);

        $branch = $service->create($family, new FamilyBranchData(
            name: 'Cabang Bandung',
            description: 'Keluarga wilayah Bandung',
        ));

        $this->assertSame($family->id, $branch->family_id);
        $this->assertSame('Cabang Bandung', $branch->name);
    }

    public function test_rejects_branch_from_other_family(): void
    {
        $family = Family::factory()->create(['created_by' => User::factory()]);
        $otherFamily = Family::factory()->create(['created_by' => User::factory()]);
        $branch = FamilyBranch::factory()->create(['family_id' => $otherFamily->id]);
        $service = new FamilyBranchService(new EloquentFamilyBranchRepository);

        $this->expectException(ValidationException::class);

        $service->update($family, $branch, new FamilyBranchData(
            name: 'Cabang Salah',
            description: null,
        ));
    }
}
