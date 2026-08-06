<?php

namespace ModShield\Flarum\Tests\Unit;

use ModShield\Flarum\CallbackSignature;
use PHPUnit\Framework\TestCase;

class CallbackSignatureTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';

    private function sign(string $body, int $t, string $secret = self::SECRET): string
    {
        return 't=' . $t . ',v1=' . hash_hmac('sha256', $t . '.' . $body, $secret);
    }

    public function testAcceptsValidSignature(): void
    {
        $body = '{"delivery_id":"dlv_1"}';
        $now = 1754500000;
        $this->assertTrue(CallbackSignature::verify($body, $this->sign($body, $now), self::SECRET, 300, $now));
    }

    public function testRejectsWrongSecret(): void
    {
        $body = '{"a":1}';
        $now = 1754500000;
        $this->assertFalse(CallbackSignature::verify($body, $this->sign($body, $now, 'other'), self::SECRET, 300, $now));
    }

    public function testRejectsTamperedBody(): void
    {
        $now = 1754500000;
        $header = $this->sign('{"a":1}', $now);
        $this->assertFalse(CallbackSignature::verify('{"a":2}', $header, self::SECRET, 300, $now));
    }

    public function testRejectsTimestampOutsideTolerance(): void
    {
        $body = '{"a":1}';
        $now = 1754500000;
        $this->assertFalse(CallbackSignature::verify($body, $this->sign($body, $now - 301), self::SECRET, 300, $now));
        $this->assertFalse(CallbackSignature::verify($body, $this->sign($body, $now + 301), self::SECRET, 300, $now));
    }

    public function testAcceptsTimestampWithinTolerance(): void
    {
        $body = '{"a":1}';
        $now = 1754500000;
        $this->assertTrue(CallbackSignature::verify($body, $this->sign($body, $now - 299), self::SECRET, 300, $now));
    }

    public function testAcceptsWhenAnyV1EntryMatches(): void
    {
        $body = '{"a":1}';
        $now = 1754500000;
        $good = hash_hmac('sha256', $now . '.' . $body, self::SECRET);
        $header = 't=' . $now . ',v1=' . str_repeat('0', 64) . ',v1=' . $good;
        $this->assertTrue(CallbackSignature::verify($body, $header, self::SECRET, 300, $now));
    }

    public function testRejectsMalformedHeaderEmptySecretEmptyBody(): void
    {
        $now = 1754500000;
        $this->assertFalse(CallbackSignature::verify('{"a":1}', 'garbage', self::SECRET, 300, $now));
        $this->assertFalse(CallbackSignature::verify('{"a":1}', '', self::SECRET, 300, $now));
        $this->assertFalse(CallbackSignature::verify('{"a":1}', $this->sign('{"a":1}', $now), '', 300, $now));
        $this->assertFalse(CallbackSignature::verify('', $this->sign('', $now), self::SECRET, 300, $now));
    }
}
