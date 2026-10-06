<?php

namespace Tests\Unit;

use App\Services\TotpService;
use PHPUnit\Framework\TestCase;

class TotpServiceTest extends TestCase
{
    /**
     * RFC 6238 test secret: ASCII "12345678901234567890" base32-encoded.
     */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function test_it_matches_rfc_6238_sha1_vectors(): void
    {
        $totp = new TotpService;

        $this->assertSame('287082', $totp->code(self::RFC_SECRET, 59));
        $this->assertSame('081804', $totp->code(self::RFC_SECRET, 1111111109));
        $this->assertSame('050471', $totp->code(self::RFC_SECRET, 1111111111));
    }

    public function test_it_verifies_the_current_code_and_rejects_a_wrong_one(): void
    {
        $totp = new TotpService;
        $secret = $totp->generateSecret();

        $this->assertTrue($totp->verify($secret, $totp->code($secret)));
        $this->assertFalse($totp->verify($secret, '000000'));
        $this->assertFalse($totp->verify($secret, 'not-a-code'));
    }

    public function test_it_builds_a_provisioning_uri(): void
    {
        $uri = (new TotpService)->provisioningUri('ABCDEF', 'budi@example.com', 'Family Tree');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=ABCDEF', $uri);
        $this->assertStringContainsString('issuer=Family+Tree', $uri);
    }
}
