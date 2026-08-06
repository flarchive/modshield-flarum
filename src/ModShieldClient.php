<?php

namespace ModShield\Flarum;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class ModShieldClient
{
    private string $coreUrl;
    private string $apiKey;
    private Client $http;
    private ?LoggerInterface $logger;

    public function __construct(string $coreUrl, string $apiKey, ?LoggerInterface $logger = null, ?Client $http = null)
    {
        $this->coreUrl = rtrim($coreUrl, '/');
        $this->apiKey = $apiKey;
        $this->http = $http ?? new Client(['timeout' => 2, 'connect_timeout' => 1]);
        $this->logger = $logger;
    }

    public function check(array $payload): ?array
    {
        try {
            $response = $this->http->post($this->coreUrl . '/api/v1/check', [
                'json' => $payload,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            $this->logger?->error('[ModShield] Check failed: ' . $e->getMessage());
            return null;
        }
    }

    public function captureToken(string $surface = 'forum_post'): ?array
    {
        try {
            $response = $this->http->get($this->coreUrl . '/api/v1/capture/token', [
                'query' => ['surface' => $surface],
                'headers' => ['Authorization' => 'Bearer ' . $this->apiKey],
            ]);
            if ($response->getStatusCode() >= 300) {
                return null;
            }
            $data = json_decode($response->getBody()->getContents(), true);
            return is_array($data) ? $data : null;
        } catch (GuzzleException $e) {
            $this->logger?->error('[ModShield] Capture token fetch failed: ' . $e->getMessage());
            return null;
        }
    }

    public function feedback(array $payload): void
    {
        try {
            $this->http->post($this->coreUrl . '/api/v1/feedback', [
                'json' => $payload,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);
        } catch (GuzzleException $e) {
            $this->logger?->error('[ModShield] Feedback failed: ' . $e->getMessage());
        }
    }
}
