<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Models;

use System\Behaviors\SettingsModel;
use Throwable;
use Winter\Storm\Database\Model;

/**
 * @property array $implement
 * @mixin SettingsModel
 */
final class Settings extends Model
{
    /**
     * The settings code which to save the settings under.
     */
    public string $settingsCode = 'creativesizzle_redirect_settings';

    /**
     * Form fields definition file.
     */
    public string $settingsFields = 'fields.yaml';

    public function __construct(array $attributes = [])
    {
        $this->implement = [SettingsModel::class];

        parent::__construct($attributes);
    }

    public static function isLoggingEnabled(): bool
    {
        try {
            return (bool) (new self())->get('logging_enabled', false);
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function isStatisticsEnabled(): bool
    {
        try {
            return (bool) (new self())->get('statistics_enabled', false);
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function isTestLabEnabled(): bool
    {
        try {
            return (bool) (new self())->get('test_lab_enabled', false);
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function isCachingEnabled(): bool
    {
        try {
            return (bool) (new self())->get('caching_enabled', false);
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function isRelativePathsEnabled(): bool
    {
        try {
            return (bool) (new self())->get('relative_paths_enabled', true);
        } catch (Throwable $exception) {
            return true;
        }
    }

    public static function httpRedirectCache(): int
    {
        try {
            return (int) (new self())->get('http_redirect_cache', 1);
        } catch (Throwable $exception) {
            return 1;
        }
    }
}
