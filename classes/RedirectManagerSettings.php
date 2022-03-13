<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes;

use CreativeSizzle\Redirect\Models\Settings;

final class RedirectManagerSettings
{
    private bool $loggingEnabled;

    private bool $statisticsEnabled;

    private bool $relativePathsEnabled;

    private int $httpRedirectCache;

    public function __construct(bool $loggingEnabled, bool $statisticsEnabled, bool $relativePathsEnabled, int $httpRedirectCache)
    {
        $this->loggingEnabled = $loggingEnabled;
        $this->statisticsEnabled = $statisticsEnabled;
        $this->relativePathsEnabled = $relativePathsEnabled;
        $this->httpRedirectCache = $httpRedirectCache;
    }

    public static function createDefault(): RedirectManagerSettings
    {
        return new self(
            Settings::isLoggingEnabled(),
            Settings::isStatisticsEnabled(),
            Settings::isRelativePathsEnabled(),
            Settings::httpRedirectCache()
        );
    }

    public function isLoggingEnabled(): bool
    {
        return $this->loggingEnabled;
    }

    public function isStatisticsEnabled(): bool
    {
        return $this->statisticsEnabled;
    }

    public function isRelativePathsEnabled(): bool
    {
        return $this->relativePathsEnabled;
    }

    public function httpRedirectCache(): int
    {
        return $this->httpRedirectCache;
    }
}
