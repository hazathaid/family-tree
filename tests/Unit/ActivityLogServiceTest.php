<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\FamilyMember;
use App\Models\User;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Services\ActivityLogService;
use Mockery;
use PHPUnit\Framework\TestCase;

class ActivityLogServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_records_a_member_created_activity(): void
    {
        $repository = Mockery::mock(ActivityLogRepositoryInterface::class);
        $service = new ActivityLogService($repository);
        $user = new User(['name' => 'Admin']);
        $user->id = 7;
        $member = new FamilyMember(['uuid' => 'member-uuid', 'full_name' => 'Budi Santoso']);
        $member->family_id = 11;
        $activity = new ActivityLog(['activity_type' => ActivityLog::MEMBER_CREATED]);

        $repository->shouldReceive('create')->once()->with([
            'family_id' => 11,
            'user_id' => 7,
            'activity_type' => ActivityLog::MEMBER_CREATED,
            'payload' => ['subject_uuid' => 'member-uuid', 'name' => 'Budi Santoso'],
        ])->andReturn($activity);

        $this->assertSame($activity, $service->memberCreated($user, $member));
    }
}
