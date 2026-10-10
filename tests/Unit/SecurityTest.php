<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\CSRF;
use App\Helpers\Sanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Unit test keamanan inti (T-003): CSRF + Sanitizer.
 */
class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testGenerateTokenReturns64CharHex(): void
    {
        $token = CSRF::generateToken();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        $this->assertSame($token, $_SESSION[CSRF::SESSION_KEY]);
    }

    public function testGenerateTokenIsStableWithinSession(): void
    {
        $first = CSRF::generateToken();
        $second = CSRF::generateToken();

        $this->assertSame($first, $second);
    }

    public function testVerifyTokenAcceptsCorrectToken(): void
    {
        $token = CSRF::generateToken();

        $this->assertTrue(CSRF::verifyToken($token));
    }

    public function testVerifyTokenRejectsWrongToken(): void
    {
        CSRF::generateToken();

        $this->assertFalse(CSRF::verifyToken('token-salah'));
    }

    public function testVerifyTokenRejectsEmptySession(): void
    {
        $this->assertFalse(CSRF::verifyToken('apa-pun'));
        $this->assertFalse(CSRF::verifyToken(null));
        $this->assertFalse(CSRF::verifyToken(''));
    }

    public function testFieldRendersHiddenInputWithToken(): void
    {
        $token = CSRF::generateToken();
        $field = CSRF::field();

        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString($token, $field);
    }

    public function testEscapeNeutralizesXssPayload(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;',
            Sanitizer::escape('<script>alert("x")</script>')
        );
    }

    public function testEscapeHandlesQuotesAndAmpersand(): void
    {
        $this->assertSame(
            'a &amp; &#039;b&#039; &quot;c&quot;',
            Sanitizer::escape('a & \'b\' "c"')
        );
    }

    public function testNormalizeLicensePlate(): void
    {
        $this->assertSame('BK1234ABC', Sanitizer::normalizeLicensePlate('bk  1234  abc'));
        $this->assertSame('BK1234ABC', Sanitizer::normalizeLicensePlate('BK1234ABC'));
        $this->assertSame('BK1234ABC', Sanitizer::normalizeLicensePlate('  bk 1234 abc  '));
    }
}
