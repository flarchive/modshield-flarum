<?php

namespace ModShield\Flarum;

use Psr\Log\LoggerInterface;

/**
 * Builds a configured {@see ModShieldClient}, wiring in the logger so HTTP
 * failures are recorded instead of being swallowed silently.
 */
class ModShieldClientFactory
{
    public function __construct(
        private ModShieldSettings $settings,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return ModShieldClient|null null when the extension is not configured
     *                             (missing core URL or API key).
     */
    public function make(): ?ModShieldClient
    {
        if (!$this->settings->isConfigured()) {
            return null;
        }

        return new ModShieldClient(
            $this->settings->coreUrl(),
            $this->settings->apiKey(),
            $this->logger,
        );
    }
}
