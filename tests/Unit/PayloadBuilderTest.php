<?php

namespace ModShield\Flarum\Tests\Unit;

use ModShield\Flarum\PayloadBuilder;
use PHPUnit\Framework\TestCase;

class PayloadBuilderTest extends TestCase
{
    public function testExtractsLinksFromContent(): void
    {
        $links = PayloadBuilder::extractLinks('Check https://example.com and http://spam.biz/offer out');

        $this->assertCount(2, $links);
        $this->assertSame('example.com', $links[0]['domain']);
        $this->assertSame('spam.biz', $links[1]['domain']);
        $this->assertSame('https://example.com', $links[0]['url']);
    }

    public function testExtractsNoLinksFromPlainText(): void
    {
        $links = PayloadBuilder::extractLinks('Just a normal post without any links');

        $this->assertCount(0, $links);
    }

    public function testExtractsLinksIgnoresMarkdown(): void
    {
        $links = PayloadBuilder::extractLinks('Visit [my site](https://example.com) for more');

        $this->assertCount(1, $links);
        $this->assertSame('example.com', $links[0]['domain']);
    }

    public function testExtractsLinksHandlesMultipleSameDomain(): void
    {
        $links = PayloadBuilder::extractLinks('https://spam.com/a https://spam.com/b');

        $this->assertCount(2, $links);
        $this->assertSame('spam.com', $links[0]['domain']);
        $this->assertSame('spam.com', $links[1]['domain']);
    }

    public function testFeedbackPayloadStructure(): void
    {
        $payload = PayloadBuilder::feedbackPayload(42, 'spam');

        $this->assertSame('flarum_post_42', $payload['event_id']);
        $this->assertSame('spam', $payload['feedback']);
        $this->assertArrayNotHasKey('decision_id', $payload);
    }

    public function testFeedbackPayloadFalsePositive(): void
    {
        $payload = PayloadBuilder::feedbackPayload(99, 'false_positive');

        $this->assertSame('flarum_post_99', $payload['event_id']);
        $this->assertSame('false_positive', $payload['feedback']);
    }

    public function testFromPostSendsRawEmailAndIpNotHash(): void
    {
        $user = (object) [
            'id' => 7,
            'username' => 'spammer',
            'email' => 'spammer@evil.com',
            'joined_at' => null,
            'comment_count' => 3,
        ];
        $discussion = (object) ['id' => 12, 'title' => 'A thread'];
        $post = (object) [
            'id' => 99,
            'created_at' => null,
            'number' => 1,
            'content' => 'hello world',
            'ip_address' => '203.0.113.7',
            'user' => $user,
            'discussion' => $discussion,
        ];

        $payload = PayloadBuilder::fromPost($post, 'content.created');

        $this->assertSame('spammer@evil.com', $payload['actor']['email']);
        $this->assertSame('203.0.113.7', $payload['actor']['ip']);
        $this->assertArrayNotHasKey('email_hash', $payload['actor']);
        $this->assertSame('1.1.0', $payload['metadata']['connector_version']);
    }

    public function testFromPostUsesConfiguredModeratorGroupId(): void
    {
        $groups = new class {
            public function contains(string $key, int $id): bool
            {
                return $key === 'id' && $id === 7;
            }
        };
        $user = (object) [
            'id' => 3,
            'username' => 'mod',
            'email' => 'mod@forum.test',
            'joined_at' => null,
            'comment_count' => 50,
            'groups' => $groups,
        ];
        $post = (object) [
            'id' => 1,
            'created_at' => null,
            'number' => 2,
            'content' => 'hi',
            'ip_address' => null,
            'user' => $user,
            'discussion' => (object) ['id' => 1, 'title' => 'T'],
        ];

        // Group 7 is the configured moderator group -> role 'moderator'.
        $moderator = PayloadBuilder::fromPost($post, 'content.created', 7);
        $this->assertSame('moderator', $moderator['actor']['role']);

        // Default group id (4) does not match -> role 'member'.
        $member = PayloadBuilder::fromPost($post, 'content.created');
        $this->assertSame('member', $member['actor']['role']);
    }
}
