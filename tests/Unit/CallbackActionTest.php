<?php

namespace ModShield\Flarum\Tests\Unit;

use ModShield\Flarum\CallbackAction;
use PHPUnit\Framework\TestCase;

class CallbackActionTest extends TestCase
{
    private const NOW = 1754500000;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'delivery_id' => 'dlv_1',
            'content_external_id' => 'post_42',
            'recommended_action' => 'block',
            'trigger' => 'shieldstral_flag',
            'expires_at' => date('c', self::NOW + 3600),
        ], $overrides);
    }

    private function makePost(bool $hidden = false): object
    {
        return new class($hidden) {
            public bool $saved = false;
            public ?string $hidden_at;
            public function __construct(bool $hidden) { $this->hidden_at = $hidden ? '2026-08-06T00:00:00+00:00' : null; }
            public function hide(): void { $this->hidden_at = '2026-08-07T00:00:00+00:00'; }
            public function save(): void { $this->saved = true; }
        };
    }

    public function testExpiredPayloadDoesNothing(): void
    {
        $post = $this->makePost();
        $result = (new CallbackAction())->apply(
            $this->payload(['expires_at' => date('c', self::NOW - 10)]), $post, 'active', self::NOW
        );
        $this->assertSame('expired', $result);
        $this->assertFalse($post->saved);
    }

    public function testMissingPostReturnsNotFound(): void
    {
        $result = (new CallbackAction())->apply($this->payload(), null, 'active', self::NOW);
        $this->assertSame('not_found', $result);
    }

    public function testInactiveModeObservesOnly(): void
    {
        $post = $this->makePost();
        $result = (new CallbackAction())->apply($this->payload(), $post, 'disabled', self::NOW);
        $this->assertSame('observed', $result);
        $this->assertFalse($post->saved);
    }

    public function testBlockHidesVisiblePost(): void
    {
        $post = $this->makePost();
        $result = (new CallbackAction())->apply($this->payload(), $post, 'active', self::NOW);
        $this->assertSame('applied', $result);
        $this->assertNotNull($post->hidden_at);
        $this->assertTrue($post->saved);
    }

    public function testSendToReviewAlsoHides(): void
    {
        $post = $this->makePost();
        $result = (new CallbackAction())->apply(
            $this->payload(['recommended_action' => 'send_to_review']), $post, 'active', self::NOW
        );
        $this->assertSame('applied', $result);
        $this->assertNotNull($post->hidden_at);
    }

    public function testAlreadyHiddenPostIsNoop(): void
    {
        $post = $this->makePost(true);
        $result = (new CallbackAction())->apply($this->payload(), $post, 'active', self::NOW);
        $this->assertSame('noop', $result);
        $this->assertFalse($post->saved);
    }

    public function testAllowRecommendationIsNoop(): void
    {
        $post = $this->makePost();
        $result = (new CallbackAction())->apply(
            $this->payload(['recommended_action' => 'allow']), $post, 'active', self::NOW
        );
        $this->assertSame('noop', $result);
        $this->assertFalse($post->saved);
    }
}
