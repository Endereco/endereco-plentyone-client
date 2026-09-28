<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\EnderecoRpc;
use EnderecoPlentyOneClient\Enums\EnqueueMode;
use EnderecoPlentyOneClient\Enums\OrderStatus;
use EnderecoPlentyOneClient\Enums\Plugin;
use EnderecoPlentyOneClient\Enums\ProcessingMode;
use EnderecoPlentyOneClient\Enums\PropertyValue;
use EnderecoPlentyOneClient\Enums\SettingKey;
use EnderecoPlentyOneClient\Enums\TargetStatusCase;
use Plenty\Plugin\ConfigRepository;

/**
 * Typed access to the plugin's settings.
 *
 * A stored setting always comes back as a string, so every read goes
 * through here and hands out something the rest of the plugin can work
 * with. Changes in the backend take effect without a deploy.
 */
final class PluginSettings
{
    public function __construct(
        private ConfigRepository $config,
    ) {
    }

    /** True when every new order should be queued right away. */
    public function enqueuesAutomatically(): bool
    {
        return $this->enqueueMode() === EnqueueMode::AUTOMATIC;
    }

    /** Falls back to AUTOMATIC when unset - the safer default after install. */
    public function enqueueMode(): string
    {
        $value = $this->text(SettingKey::ENQUEUE_MODE, EnqueueMode::AUTOMATIC);

        if ($value === EnqueueMode::MANUAL) {
            return EnqueueMode::MANUAL;
        }

        return EnqueueMode::AUTOMATIC;
    }

    /** Falls back to RUNNING when unset. */
    public function processingMode(): string
    {
        $value = $this->text(SettingKey::PROCESSING_MODE, ProcessingMode::RUNNING);

        if (in_array($value, [ProcessingMode::DRAINING, ProcessingMode::STOPPED], true)) {
            return $value;
        }

        return ProcessingMode::RUNNING;
    }

    /** False while draining or fully stopped. */
    public function acceptsNewOrders(): bool
    {
        return $this->processingMode() === ProcessingMode::RUNNING;
    }

    /** False only when fully stopped - draining still works through what is there. */
    public function processesQueue(): bool
    {
        return $this->processingMode() !== ProcessingMode::STOPPED;
    }

    /** Only an explicit "false" switches this off; empty defaults to on. */
    public function appliesCorrectionAutomatically(): bool
    {
        $value = $this->text(SettingKey::APPLY_CORRECTION, PropertyValue::TRUE);

        return $value !== PropertyValue::FALSE;
    }

    /** Only an explicit "false" switches this off; empty defaults to on. */
    public function writesComments(): bool
    {
        $value = $this->text(SettingKey::WRITE_COMMENTS, PropertyValue::TRUE);

        return $value !== PropertyValue::FALSE;
    }

    /**
     * Backend user id for the plugin's own order comments. Falls back to 1
     * for an empty or non-positive value - see OrderCommentWriter for how a
     * nonexistent id is caught.
     */
    public function commentUserId(): int
    {
        $userId = (int) $this->text(SettingKey::COMMENT_USER_ID, '1');

        return $userId > 0 ? $userId : 1;
    }

    /**
     * Order status an order starts its check on. Falls back to
     * OrderStatus::HOLD for an empty, "0", or unparseable value - unlike
     * targetStatus(), "0" is not a special case here. Existence as a real
     * status is checked in StatusResolver::resolveStart(), not here.
     */
    public function startStatus(): float
    {
        $value = (float) $this->text(SettingKey::START_STATUS, OrderStatus::HOLD);

        return $value > 0.0 ? $value : (float) OrderStatus::HOLD;
    }

    /**
     * The configured target status for one TargetStatusCase, or null for
     * "0" (also the fallback for empty/unparseable) - meaning: restore the
     * order's previous status. Existence as a real status is checked in
     * StatusResolver::resolveTarget(), not here.
     *
     * GAVE_UP is not configurable - always restores the previous status.
     */
    public function targetStatus(string $case): ?float
    {
        if ($case === TargetStatusCase::GAVE_UP) {
            return null;
        }

        $key = match ($case) {
            TargetStatusCase::CORRECT => SettingKey::TARGET_STATUS_CORRECT,
            TargetStatusCase::MINOR_CORRECTION => SettingKey::TARGET_STATUS_MINOR_CORRECTION,
            TargetStatusCase::SUGGESTION_AVAILABLE,
            TargetStatusCase::MULTIPLE_VARIANTS => SettingKey::TARGET_STATUS_SUGGESTION_AVAILABLE,
            TargetStatusCase::NOT_FOUND => SettingKey::TARGET_STATUS_NOT_FOUND,
        };

        $value = (float) $this->text($key, '0');

        return $value === 0.0 ? null : $value;
    }

    /** Empty when the merchant has not entered a key yet. */
    public function apiKey(): string
    {
        return $this->text(SettingKey::API_KEY, '');
    }

    public function apiEndpoint(): string
    {
        $endpoint = $this->text(SettingKey::API_ENDPOINT, EnderecoRpc::DEFAULT_ENDPOINT);

        if ($endpoint === '') {
            return EnderecoRpc::DEFAULT_ENDPOINT;
        }

        return $endpoint;
    }

    private function text(string $key, string $default): string
    {
        $value = $this->config->get(
            Plugin::NAMESPACE . '.' . $key,
            $default
        );

        return is_string($value) ? $value : $default;
    }
}
