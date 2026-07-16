<?php

namespace Tests\Unit;

use App\Services\RichTextSanitizer;
use PHPUnit\Framework\TestCase;

class RichTextSanitizerTest extends TestCase
{
    public function test_it_removes_scripts_handlers_and_unsafe_urls(): void
    {
        $result = (new RichTextSanitizer)->sanitize('<p onclick="x">Safe<script>x</script><a href="javascript:x">link</a></p>');
        $this->assertStringNotContainsString('script', $result);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('javascript', $result);
        $this->assertStringContainsString('<p>Safe', $result);
    }
}
