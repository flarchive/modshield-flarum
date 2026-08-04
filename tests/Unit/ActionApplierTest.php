<?php

namespace ModShield\Flarum\Tests\Unit;

use ModShield\Flarum\ActionApplier;
use PHPUnit\Framework\TestCase;

class ActionApplierTest extends TestCase
{
    public function testAllowDoesNotHidePost(): void
    {
        $post = $this->createMockPost();
        $post->expects($this->never())->method('hide');
        $post->expects($this->never())->method('save');

        ActionApplier::apply($post, ['effective_action' => 'allow'], 'active');
    }

    public function testSendToReviewHidesPost(): void
    {
        $post = $this->createMockPost();
        $post->expects($this->once())->method('hide');
        $post->expects($this->once())->method('save');

        ActionApplier::apply($post, ['effective_action' => 'send_to_review'], 'active');
    }

    public function testBlockHidesPost(): void
    {
        $post = $this->createMockPost();
        $post->expects($this->once())->method('hide');
        $post->expects($this->once())->method('save');

        $applied = ActionApplier::apply($post, ['effective_action' => 'block'], 'active');

        $this->assertSame('block', $applied);
    }

    public function testReturnsAppliedAction(): void
    {
        $this->assertSame(
            'send_to_review',
            ActionApplier::apply($this->createMockPost(), ['effective_action' => 'send_to_review'], 'active')
        );
        $this->assertSame(
            'allow',
            ActionApplier::apply($this->createMockPost(), ['effective_action' => 'allow'], 'active')
        );
        $this->assertNull(
            ActionApplier::apply($this->createMockPost(), ['effective_action' => 'block'], 'shadow')
        );
    }

    public function testShadowModeNeverHides(): void
    {
        $post = $this->createMockPost();
        $post->expects($this->never())->method('hide');

        ActionApplier::apply($post, ['effective_action' => 'block'], 'shadow');
    }

    public function testDisabledModeNeverHides(): void
    {
        $post = $this->createMockPost();
        $post->expects($this->never())->method('hide');

        ActionApplier::apply($post, ['effective_action' => 'block'], 'disabled');
    }

    public function testMissingActionDefaultsToAllow(): void
    {
        $post = $this->createMockPost();
        $post->expects($this->never())->method('hide');

        ActionApplier::apply($post, [], 'active');
    }

    private function createMockPost(): object
    {
        return $this->getMockBuilder(\stdClass::class)
            ->addMethods(['hide', 'save'])
            ->getMock();
    }
}
