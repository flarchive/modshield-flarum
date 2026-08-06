<?php

namespace ModShield\Flarum\Controller;

use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use ModShield\Flarum\ModShieldClientFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Proxies capture-token issuance so the browser never sees the API key.
 */
class CaptureTokenController implements RequestHandlerInterface
{
    public function __construct(private ModShieldClientFactory $factory) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertRegistered();

        $client = $this->factory->make();
        if ($client === null) {
            return new EmptyResponse(502);
        }

        $token = $client->captureToken();
        if ($token === null) {
            return new EmptyResponse(502);
        }

        return new JsonResponse($token);
    }
}
