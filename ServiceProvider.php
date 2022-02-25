<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect;

use Winter\Storm\Support\ServiceProvider as ServiceProviderBase;
use CreativeSizzle\Redirect\Classes\CacheManager;
use CreativeSizzle\Redirect\Classes\Contracts;
use CreativeSizzle\Redirect\Classes\PublishManager;
use CreativeSizzle\Redirect\Classes\RedirectManager;

final class ServiceProvider extends ServiceProviderBase
{
    public function register(): void
    {
        $this->app->bind(Contracts\RedirectManagerInterface::class, RedirectManager::class);
        $this->app->bind(Contracts\PublishManagerInterface::class, PublishManager::class);
        $this->app->bind(Contracts\CacheManagerInterface::class, CacheManager::class);

        $this->app->singleton(RedirectManager::class);
        $this->app->singleton(PublishManager::class);
        $this->app->singleton(CacheManager::class);
    }
}
