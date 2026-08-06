<?php

namespace ModShield\Flarum\Controller;

use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use ModShield\Flarum\CallbackAction;
use ModShield\Flarum\CallbackSignature;
use ModShield\Flarum\ModShieldSettings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Public endpoint for ModShield Core signed callbacks. Security is the HMAC
 * signature alone: 503 when the secret is not configured (operator error —
 * Core keeps retrying), 401 on bad signature (no detail), 2xx for every
 * verified payload so Core stops retrying.
 */
class CallbackController implements RequestHandlerInterface
{
    public function __construct(
        private ModShieldSettings $settings,
        private CallbackAction $action,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $secret = $this->settings->callbackSecret();
        if ($secret === '') {
            return new EmptyResponse(503);
        }

        $rawBody = (string) $request->getBody();
        $signature = $request->getHeaderLine('X-ModShield-Signature');

        if (!CallbackSignature::verify($rawBody, $signature, $secret)) {
            return new EmptyResponse(401);
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return new JsonResponse(['status' => 'ignored'], 200);
        }

        $post = null;
        $externalId = $payload['content_external_id'] ?? '';
        if (is_string($externalId) && preg_match('/^post_(\d+)$/', $externalId, $m)) {
            $found = Post::find((int) $m[1]);
            $post = $found instanceof CommentPost ? $found : null;
        }

        $status = $this->action->apply($payload, $post, $this->settings->mode());

        return new JsonResponse(['status' => $status], 200);
    }
}
