<?php

namespace ModShield\Flarum;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Typed accessors over the extension's settings, so listeners and jobs no
 * longer duplicate raw `$settings->get('modshield.*')` reads.
 */
class ModShieldSettings
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {}

    public function mode(): string
    {
        return (string) $this->settings->get('modshield.mode', 'disabled');
    }

    public function isEnabled(): bool
    {
        return $this->mode() !== 'disabled';
    }

    public function coreUrl(): string
    {
        return (string) $this->settings->get('modshield.core_url', '');
    }

    public function apiKey(): string
    {
        return (string) $this->settings->get('modshield.api_key', '');
    }

    public function isConfigured(): bool
    {
        return $this->coreUrl() !== '' && $this->apiKey() !== '';
    }

    public function failClosed(): bool
    {
        return $this->settings->get('modshield.fail_strategy', 'fail_open') === 'fail_closed';
    }

    public function moderatorGroupId(): int
    {
        $value = $this->settings->get('modshield.moderator_group_id');

        return $value !== null && $value !== ''
            ? (int) $value
            : PayloadBuilder::DEFAULT_MODERATOR_GROUP_ID;
    }
}
