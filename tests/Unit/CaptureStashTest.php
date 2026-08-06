<?php

namespace ModShield\Flarum\Tests\Unit;

use ModShield\Flarum\CaptureStash;
use PHPUnit\Framework\TestCase;

class CaptureStashTest extends TestCase
{
    public function testPutThenPullReturnsCaptureAndClears(): void
    {
        $post = new class {};
        $capture = ['token' => 'tok_1', 'fill_ms' => 500, 'honeypot' => null];

        CaptureStash::put($post, $capture);

        $this->assertSame($capture, CaptureStash::pull($post));
        $this->assertNull(CaptureStash::pull($post));
    }

    public function testPullWithoutPutReturnsNull(): void
    {
        $post = new class {};

        $this->assertNull(CaptureStash::pull($post));
    }

    public function testDistinctObjectsHaveDistinctStashEntries(): void
    {
        $postA = new class {};
        $postB = new class {};

        CaptureStash::put($postA, ['token' => 'a']);
        CaptureStash::put($postB, ['token' => 'b']);

        $this->assertSame(['token' => 'b'], CaptureStash::pull($postB));
        $this->assertSame(['token' => 'a'], CaptureStash::pull($postA));
    }
}
