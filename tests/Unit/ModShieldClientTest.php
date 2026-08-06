<?php

namespace ModShield\Flarum\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use ModShield\Flarum\ModShieldClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ModShieldClientTest extends TestCase
{
    public function testCheckReturnsDecodedDecisionOnSuccess(): void
    {
        $client = $this->clientWith([
            new Response(200, [], json_encode(['effective_action' => 'block'])),
        ]);

        $decision = $client->check(['event_id' => 'x']);

        $this->assertSame(['effective_action' => 'block'], $decision);
    }

    public function testCheckReturnsNullAndLogsOnFailure(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('[ModShield] Check failed'));

        $client = $this->clientWith([
            new ConnectException('timeout', new Request('POST', 'check')),
        ], $logger);

        $this->assertNull($client->check(['event_id' => 'x']));
    }

    public function testFeedbackSwallowsErrorsAndLogs(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('[ModShield] Feedback failed'));

        $client = $this->clientWith([
            new ConnectException('timeout', new Request('POST', 'feedback')),
        ], $logger);

        // Should not throw.
        $client->feedback(['event_id' => 'x', 'feedback' => 'spam']);
    }

    public function testCaptureTokenReturnsDecodedJson(): void
    {
        $client = $this->clientWith([new Response(200, [], '{"token":"tok_1","expires_in":600}')]);
        $this->assertSame(['token' => 'tok_1', 'expires_in' => 600], $client->captureToken());
    }

    public function testCaptureTokenReturnsNullOnFailure(): void
    {
        $client = $this->clientWith([new Response(500)]);
        $this->assertNull($client->captureToken());
    }

    private function clientWith(array $queue, ?LoggerInterface $logger = null): ModShieldClient
    {
        $handler = HandlerStack::create(new MockHandler($queue));
        $http = new Client(['handler' => $handler]);

        return new ModShieldClient('https://core.example.com', 'ms_test_key', $logger, $http);
    }
}
