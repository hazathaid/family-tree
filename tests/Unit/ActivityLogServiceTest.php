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

    /**
     * @dataProvider memberActivityMethods
     */
    public function test_it_records_member_mutation_activities(string $method, string $type): void
    {
        $repository = Mockery::mock(ActivityLogRepositoryInterface::class);
        $service = new ActivityLogService($repository);
        $user = new User(['name' => 'Admin']);
        $user->id = 7;
        $member = new FamilyMember(['uuid' => 'member-uuid', 'full_name' => 'Budi Santoso']);
        $member->family_id = 11;
        $activity = new ActivityLog(['activity_type' => $type]);

        $repository->shouldReceive('create')->once()->with([
            'family_id' => 11,
            'user_id' => 7,
            'activity_type' => $type,
            'payload' => ['subject_uuid' => 'member-uuid', 'name' => 'Budi Santoso'],
        ])->andReturn($activity);

        $this->assertSame($activity, $service->{$method}($user, $member));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function memberActivityMethods(): array
    {
        return [
            'created' => ['memberCreated', ActivityLog::MEMBER_CREATED],
            'updated' => ['memberUpdated', ActivityLog::MEMBER_UPDATED],
            'deleted' => ['memberDeleted', ActivityLog::MEMBER_DELETED],
            'photo updated' => ['memberPhotoUpdated', ActivityLog::MEMBER_PHOTO_UPDATED],
        ];
    }
}
