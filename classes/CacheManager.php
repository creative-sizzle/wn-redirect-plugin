<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes;

use Carbon\Carbon;
use CreativeSizzle\Redirect\Classes\Contracts\CacheManagerInterface;
use CreativeSizzle\Redirect\Classes\Contracts\PublishManagerInterface;
use CreativeSizzle\Redirect\Models\Settings;
use Illuminate\Contracts\Cache\Repository;
use Psr\Log\LoggerInterface;
use Throwable;

final class CacheManager implements CacheManagerInterface
{
    private const CACHE_TAG = 'creativesizzle_redirect';

    private const CACHE_TAG_RULES = 'creativesizzle_redirect_rules';

    private const CACHE_TAG_MATCHES = 'creativesizzle_redirect_matches';

    private Repository $cache;

    private LoggerInterface $log;

    public function __construct(Repository $cache, LoggerInterface $log)
    {
        $this->cache = $cache;
        $this->log = $log;
    }

    public function get(string $key)
    {
        return $this->cache->tags(self::CACHE_TAG_MATCHES)
            ->get($key);
    }

    public function forget(string $key): bool
    {
        return $this->cache->tags(self::CACHE_TAG_MATCHES)
            ->forget($key);
    }

    public function has(string $key): bool
    {
        return $this->cache->tags(self::CACHE_TAG_MATCHES)
            ->has($key);
    }

    public function cacheKey(string $requestPath, string $scheme): string
    {
        // Most caching backend have no limits on key lengths.
        // But to be sure I chose to MD5 hash the cache key.
        return md5($requestPath.$scheme);
    }

    public function flush(): void
    {
        $this->cache->tags([self::CACHE_TAG, self::CACHE_TAG_RULES, self::CACHE_TAG_MATCHES])
            ->flush();

        if ((bool) config('creativesizzle.redirect::log_redirect_changes', false) === true) {
            $this->log->info('CreativeSizzle.Redirect: Redirect cache has been flushed.');
        }
    }

    public function putRedirectRules(array $redirectRules): void
    {
        $this->cache->tags(self::CACHE_TAG_RULES)
            ->forever('rules', $redirectRules);
    }

    public function getRedirectRules(): array
    {
        if (! $this->cache->tags(self::CACHE_TAG_RULES)->has('rules')) {
            $publishManager = resolve(PublishManagerInterface::class);
            $publishManager->publish();
        }

        $data = $this->cache->tags(self::CACHE_TAG_RULES)
            ->get('rules', []);

        if (is_array($data)) {
            return $data;
        }

        return [];
    }

    public function putMatch(string $cacheKey, ?RedirectRule $matchedRule = null): ?RedirectRule
    {
        if ($matchedRule === null) {
            $this->cache->tags(self::CACHE_TAG_MATCHES)
                ->forever($cacheKey, false);

            return null;
        }

        $matchedRuleToDate = $matchedRule->getToDate();

        if ($matchedRuleToDate instanceof Carbon) {
            $minutes = $matchedRuleToDate->diffInMinutes(Carbon::now());

            $this->cache->tags(self::CACHE_TAG_MATCHES)
                ->put($cacheKey, $matchedRule, $minutes);
        } else {
            $this->cache->tags(self::CACHE_TAG_MATCHES)
                ->forever($cacheKey, $matchedRule);
        }

        return $matchedRule;
    }

    public function cachingEnabledAndSupported(): bool
    {
        if (! Settings::isCachingEnabled()) {
            return false;
        }

        try {
            $this->cache->tags(self::CACHE_TAG);
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }

    public function cachingEnabledButNotSupported(): bool
    {
        if (! Settings::isCachingEnabled()) {
            return false;
        }

        try {
            $this->cache->tags(self::CACHE_TAG);
        } catch (Throwable $e) {
            return true;
        }

        return false;
    }
}
