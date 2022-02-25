<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect;

use Backend\Facades\Backend;
use CreativeSizzle\Redirect\Classes\Contracts\PublishManagerInterface;
use CreativeSizzle\Redirect\Classes\Observers;
use CreativeSizzle\Redirect\Classes\RedirectMiddleware;
use CreativeSizzle\Redirect\Console\PublishRedirectsCommand;
use Event;
use Exception;
use Illuminate\Contracts\Translation\Translator;
use System\Classes\PluginBase;
use Throwable;
use Validator;

final class Plugin extends PluginBase
{
    /**
     * @throws Exception
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole() || $this->app->runningUnitTests()) {
            return;
        }

        $this->registerCustomValidators();
        $this->registerObservers();

        if (! $this->app->runningInBackend()) {
            $this->app['Illuminate\Contracts\Http\Kernel']
                ->prependMiddleware(RedirectMiddleware::class);
        }
    }

    public function register(): void
    {
        $this->app->register(ServiceProvider::class);

        $this->registerConsoleCommands();
        $this->registerEventListeners();
    }

    public function registerNavigation(): array
    {
        $defaultBackendUrl = Backend::url(
            'creativesizzle/redirect/' . (Models\Settings::isStatisticsEnabled() ? 'statistics' : 'redirects')
        );

        $navigation = [
            'redirect' => [
                'label' => 'creativesizzle.redirect::lang.navigation.menu_label',
                'iconSvg' => '/plugins/creativesizzle/redirect/assets/images/icon.svg',
                'icon' => 'icon-link',
                'url' => $defaultBackendUrl,
                'order' => 201,
                'permissions' => [
                    'creativesizzle.redirect.access_redirects',
                ],
                'sideMenu' => [
                    'redirects' => [
                        'icon' => 'icon-list',
                        'label' => 'creativesizzle.redirect::lang.navigation.menu_label',
                        'url' => Backend::url('creativesizzle/redirect/redirects'),
                        'order' => 20,
                        'permissions' => [
                            'creativesizzle.redirect.access_redirects',
                        ],
                    ],
                    'categories' => [
                        'label' => 'creativesizzle.redirect::lang.buttons.categories',
                        'url' => Backend::url('creativesizzle/redirect/categories'),
                        'icon' => 'icon-tag',
                        'order' => 60,
                        'permissions' => [
                            'creativesizzle.redirect.access_redirects',
                        ],
                    ],
                    'import' => [
                        'label' => 'creativesizzle.redirect::lang.buttons.import',
                        'url' => Backend::url('creativesizzle/redirect/redirects/import'),
                        'icon' => 'icon-download',
                        'order' => 70,
                        'permissions' => [
                            'creativesizzle.redirect.access_redirects',
                        ],
                    ],
                    'export' => [
                        'label' => 'creativesizzle.redirect::lang.buttons.export',
                        'url' => Backend::url('creativesizzle/redirect/redirects/export'),
                        'icon' => 'icon-upload',
                        'order' => 80,
                        'permissions' => [
                            'creativesizzle.redirect.access_redirects',
                        ],
                    ],
                    'settings' => [
                        'label' => 'creativesizzle.redirect::lang.buttons.settings',
                        'url' => Backend::url('system/settings/update/creativesizzle/redirect/config'),
                        'icon' => 'icon-cogs',
                        'order' => 90,
                        'permissions' => [
                            'creativesizzle.redirect.access_redirects',
                        ],
                    ],
                    'extensions' => [
                        'label' => 'creativesizzle.redirect::lang.buttons.extensions',
                        'url' => Backend::url('creativesizzle/redirect/extensions'),
                        'icon' => 'icon-cubes',
                        'order' => 100,
                        'permissions' => [
                            'creativesizzle.redirect.access_redirects',
                        ],
                    ],
                ],
            ],
        ];

        if (Models\Settings::isStatisticsEnabled()) {
            $navigation['redirect']['sideMenu']['statistics'] = [
                'icon' => 'icon-bar-chart',
                'label' => 'creativesizzle.redirect::lang.title.statistics',
                'url' => Backend::url('creativesizzle/redirect/statistics'),
                'order' => 10,
                'permissions' => [
                    'creativesizzle.redirect.access_redirects',
                ],
            ];
        }

        if (Models\Settings::isTestLabEnabled()) {
            $navigation['redirect']['sideMenu']['test_lab'] = [
                'icon' => 'icon-flask',
                'label' => 'creativesizzle.redirect::lang.title.test_lab',
                'url' => Backend::url('creativesizzle/redirect/testlab'),
                'order' => 30,
                'permissions' => [
                    'creativesizzle.redirect.access_redirects',
                ],
            ];
        }

        if (Models\Settings::isLoggingEnabled()) {
            $navigation['redirect']['sideMenu']['logs'] = [
                'label' => 'creativesizzle.redirect::lang.buttons.logs',
                'url' => Backend::url('creativesizzle/redirect/logs'),
                'icon' => 'icon-file-text-o',
                'visible' => false,
                'order' => 50,
                'permissions' => [
                    'creativesizzle.redirect.access_redirects',
                ],
            ];
        }

        return $navigation;
    }

    public function registerSettings(): array
    {
        return [
            'config' => [
                'label' => 'creativesizzle.redirect::lang.settings.menu_label',
                'description' => 'creativesizzle.redirect::lang.settings.menu_description',
                'icon' => 'icon-link',
                'class' => Models\Settings::class,
                'order' => 600,
                'permissions' => [
                    'creativesizzle.redirect.access_redirects',
                ],
            ],
        ];
    }

    public function registerReportWidgets(): array
    {
        /** @var Translator $translator */
        $translator = resolve(Translator::class);

        $reportWidgets[ReportWidgets\CreateRedirect::class] = [
            'label' => 'creativesizzle.redirect::lang.buttons.create_redirect',
            'context' => 'dashboard',
        ];

        if (Models\Settings::isStatisticsEnabled()) {
            $reportWidgets[ReportWidgets\TopTenRedirects::class] = [
                'label' => e($translator->trans(
                    'creativesizzle.redirect::lang.statistics.top_redirects_this_month',
                    [
                        'top' => 10,
                    ]
                )),
                'context' => 'dashboard',
            ];
        }

        return $reportWidgets;
    }

    public function registerListColumnTypes(): array
    {
        return [
            'redirect_switch_color' => static function ($value): string {
                $format = '<div class="oc-icon-circle" style="color: %s">%s</div>';

                if ((int) $value === 1) {
                    return sprintf($format, '#95b753', e(trans('backend::lang.list.column_switch_true')));
                }

                return sprintf($format, '#cc3300', e(trans('backend::lang.list.column_switch_false')));
            },
            'redirect_match_type' => static function ($value): string {
                switch ($value) {
                    case Models\Redirect::TYPE_EXACT:
                        return e(trans('creativesizzle.redirect::lang.redirect.exact'));
                    case Models\Redirect::TYPE_PLACEHOLDERS:
                        return e(trans('creativesizzle.redirect::lang.redirect.placeholders'));
                    case Models\Redirect::TYPE_REGEX:
                        return e(trans('creativesizzle.redirect::lang.redirect.regex'));
                    default:
                        return e($value);
                }
            },
            'redirect_status_code' => static function ($value): string {
                switch ($value) {
                    case 301:
                        return e(trans('creativesizzle.redirect::lang.redirect.permanent'));
                    case 302:
                        return e(trans('creativesizzle.redirect::lang.redirect.temporary'));
                    case 303:
                        return e(trans('creativesizzle.redirect::lang.redirect.see_other'));
                    case 404:
                        return e(trans('creativesizzle.redirect::lang.redirect.not_found'));
                    case 410:
                        return e(trans('creativesizzle.redirect::lang.redirect.gone'));
                    default:
                        return e($value);
                }
            },
            'redirect_target_type' => static function ($value): string {
                switch ($value) {
                    case Models\Redirect::TARGET_TYPE_PATH_URL:
                        return e(trans('creativesizzle.redirect::lang.redirect.target_type_path_or_url'));
                    case Models\Redirect::TARGET_TYPE_CMS_PAGE:
                        return e(trans('creativesizzle.redirect::lang.redirect.target_type_cms_page'));
                    case Models\Redirect::TARGET_TYPE_STATIC_PAGE:
                        return e(trans('creativesizzle.redirect::lang.redirect.target_type_static_page'));
                    default:
                        return e($value);
                }
            },
            'redirect_from_url' => static function ($value): string {
                $maxChars = 40;
                $textLength = strlen($value);

                if ($textLength > $maxChars) {
                    return '<span title="' . e($value) . '">'
                        . e(substr_replace($value, '...', $maxChars / 2, $textLength - $maxChars))
                        . '</span>';
                }

                return e($value);
            },
            'redirect_system' => static function ($value): string {
                return sprintf(
                    '<span class="%s" title="%s"></span>',
                    $value ? 'oc-icon-magic' : 'oc-icon-user',
                    e(trans('creativesizzle.redirect::lang.redirect.system_tip'))
                );
            },
        ];
    }

    public function registerSchedule($schedule): void
    {
        $schedule->command('creativesizzle:redirect:publish-redirects')
            ->dailyAt(config('creativesizzle.redirect::cron.publish_redirects', '00:00'));
    }

    private function registerConsoleCommands(): void
    {
        $this->registerConsoleCommand('creativesizzle.redirect.publish-redirects', PublishRedirectsCommand::class);
    }

    private function registerCustomValidators(): void
    {
        Validator::extend('is_regex', static function ($attribute, $value): bool {
            try {
                preg_match($value, '');
            } catch (Throwable $throwable) {
                return false;
            }

            return true;
        });
    }

    private function registerObservers(): void
    {
        Models\Redirect::observe(Observers\RedirectObserver::class);
        Models\Settings::observe(Observers\SettingsObserver::class);
    }

    private function registerEventListeners(): void
    {
        /*
         * Extensibility:
         *
         * Allows third-party plugin develop to notify when a URL has changed.
         * E.g. An editor changes the slug of a blog item.
         *
         * `Event::fire('creativesizzle.redirect.toUrlChanged', [$oldSlug, $newSlug])`
         *
         * Only 'exact' redirects will be supported.
         */
        Event::listen('creativesizzle.redirect.toUrlChanged', static function (string $oldUrl, string $newUrl): void {
            Models\Redirect::query()
                ->where('match_type', '=', Models\Redirect::TYPE_EXACT)
                ->where('target_type', '=', Models\Redirect::TARGET_TYPE_PATH_URL)
                ->where('to_url', '=', $oldUrl)
                ->where('is_enabled', '=', true)
                ->update([
                    'to_url' => $newUrl,
                    'system' => true,
                ]);
        });

        /*
         * Extensibility:
         *
         * When one or more redirects have been changed.
         */
        Event::listen('creativesizzle.redirect.changed', static function (array $redirectIds): void {
            try {
                /** @var PublishManagerInterface $publishManager */
                $publishManager = resolve(PublishManagerInterface::class);
                $publishManager->publish();
            } catch (Throwable $throwable) {
                // @ignoreException
            }
        });

        /*
         * Cache Management:
         *
         * Re-publish all redirect if cache has been cleared.
         */
        Event::listen('cache:cleared', static function (): void {
            try {
                /** @var PublishManagerInterface $publishManager */
                $publishManager = resolve(PublishManagerInterface::class);
                $publishManager->publish();
            } catch (Throwable $throwable) {
                // @ignoreException
            }
        });
    }
}
