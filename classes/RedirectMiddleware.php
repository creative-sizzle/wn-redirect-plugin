<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes;

use Closure;
use CreativeSizzle\Redirect\Classes\Contracts\CacheManagerInterface;
use CreativeSizzle\Redirect\Classes\Contracts\RedirectManagerInterface;
use CreativeSizzle\Redirect\Classes\Exceptions\InvalidScheme;
use CreativeSizzle\Redirect\Classes\Exceptions\NoMatchForRequest;
use CreativeSizzle\Redirect\Classes\Exceptions\UnableToLoadRules;
use CreativeSizzle\Redirect\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Throwable;
use Winter\Storm\Events\Dispatcher;

final class RedirectMiddleware
{
    private static array $supportedMethods = ['GET', 'POST', 'HEAD'];

    private RedirectManagerInterface $redirectManager;
    private RedirectConditionManager $redirectConditionManager;
    private CacheManagerInterface $cacheManager;
    private Dispatcher $dispatcher;
    private LoggerInterface $log;

    public function __construct(
        RedirectManagerInterface $redirectManager,
        RedirectConditionManager $redirectConditionManager,
        CacheManagerInterface $cacheManager,
        Dispatcher $dispatcher,
        LoggerInterface $log
    ) {
        $this->redirectManager = $redirectManager;
        $this->redirectConditionManager = $redirectConditionManager;
        $this->cacheManager = $cacheManager;
        $this->dispatcher = $dispatcher;
        $this->log = $log;
    }

    /**
     * Run the request filter.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only handle specific request methods.
        if (
            $request->isXmlHttpRequest()
            || ! in_array($request->method(), self::$supportedMethods, true)
            || Str::startsWith($request->getRequestUri(), '/creativesizzle/redirect/sparkline/')
        ) {
            return $next($request);
        }

        if ($request->header('X-Vdlp-Redirect') === 'Tester') {
            $this->redirectManager->setSettings(new RedirectManagerSettings(
                false,
                false,
                Settings::isRelativePathsEnabled()
            ));
        }

        $rule = false;

        $requestUri = str_replace($request->getBasePath(), '', $request->getRequestUri());

        try {
            if (
                $this->cacheManager->cachingEnabledAndSupported()
                && method_exists($this->redirectManager, 'matchCached')
            ) {
                $rule = $this->redirectManager->matchCached($requestUri, $request->getScheme());
            } else {
                $rule = $this->redirectManager->match($requestUri, $request->getScheme());
            }
        } catch (NoMatchForRequest | UnableToLoadRules | InvalidScheme $e) {
            $rule = false;
        } catch (Throwable $e) {
            $this->log->error(sprintf(
                'Vdlp.Redirect: Could not perform redirect for %s (scheme: %s): %s',
                $requestUri,
                $request->getScheme(),
                $e->getMessage()
            ));
        }

        if ($rule === false || $rule === null) {
            return $next($request);
        }

        /*
         * Extensibility:
         *
         * At this point a positive match was made based on the request URI.
         */
        $this->dispatcher->fire('creativesizzle.redirect.match', [$rule, $requestUri]);

        /*
         * Extensibility:
         *
         * Developers can add their own conditions. If a condition does not pass the redirect will be ignored.
         */
        foreach ($this->redirectConditionManager->getEnabledConditions($rule) as $condition) {
            if (! $condition->passes($rule, $requestUri)) {
                return $next($request);
            }
        }

        $this->redirectManager->redirectWithRule($rule, $requestUri);

        return $next($request);
    }
}
