<?php

namespace Tests\Unit;

use App\Services\MemorialNameService;
use PHPUnit\Framework\TestCase;

class MemorialNameServiceTest extends TestCase
{
    public function test_it_uses_religion_and_gender_for_memorial_names(): void
    {
        $service = new MemorialNameService;

        $this->assertSame('Hasan', $service->displayName(true, 'male', 'islam', 'Hasan'));
        $this->assertSame('Alm. Hasan', $service->displayName(false, 'male', 'islam', 'Hasan'));
        $this->assertSame('Almh. Aminah', $service->displayName(false, 'female', 'islam', 'Aminah'));
        $this->assertSame('† Markus', $service->displayName(false, 'male', 'christian', 'Markus'));
        $this->assertSame('† Maria', $service->displayName(false, 'female', 'catholic', 'Maria'));
        $this->assertSame('Mendiang Wayan', $service->displayName(false, 'male', 'hindu', 'Wayan'));
        $this->assertSame('Mendiang Budi', $service->displayName(false, null, null, 'Budi'));
    }
}
