<?php

namespace Tests\Unit\Security;

use App\Support\Security\Totp;
use Tests\TestCase;

class TotpTest extends TestCase
{
    // Base32 of the RFC 6238 SHA1 test key "12345678901234567890"
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function test_rfc6238_vectors(): void
    {
        // RFC 6238 Appendix B (8-digit values; we use the last 6 digits)
        $this->assertSame('287082', Totp::code(self::RFC_SECRET, intdiv(59, 30)));
        $this->assertSame('081804', Totp::code(self::RFC_SECRET, intdiv(1111111109, 30)));
        $this->assertSame('005924', Totp::code(self::RFC_SECRET, intdiv(1234567890, 30)));
    }

    public function test_verify_accepts_current_code_once(): void
    {
        $secret = Totp::generateSecret();
        $step = Totp::verify($secret, Totp::code($secret));

        $this->assertSame(Totp::currentStep(), $step);
        $this->assertNull(Totp::verify($secret, Totp::code($secret), lastUsedStep: $step)); // replay
        $this->assertNull(Totp::verify($secret, '000000x'));
    }
}
