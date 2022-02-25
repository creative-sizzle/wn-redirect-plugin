<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes;

use Cms\Classes\Page;
use Cms\Classes\Theme;
use CreativeSizzle\Redirect\Models\Category;
use CreativeSizzle\Redirect\Models\Redirect;
use System\Classes\PluginManager;

final class OptionHelper
{
    public static function getTargetTypeOptions(int $statusCode): array
    {
        if ($statusCode === 404 || $statusCode === 410) {
            return [
                Redirect::TARGET_TYPE_NONE => 'creativesizzle.redirect::lang.redirect.target_type_none',
            ];
        }

        return [
            Redirect::TARGET_TYPE_PATH_URL => 'creativesizzle.redirect::lang.redirect.target_type_path_or_url',
            Redirect::TARGET_TYPE_CMS_PAGE => 'creativesizzle.redirect::lang.redirect.target_type_cms_page',
            Redirect::TARGET_TYPE_STATIC_PAGE => 'creativesizzle.redirect::lang.redirect.target_type_static_page',
        ];
    }

    public static function getCmsPageOptions(): array
    {
        return ['' => '-- ' . e(trans('creativesizzle.redirect::lang.redirect.none')) . ' --' ] + Page::getNameList();
    }

    public static function getStaticPageOptions(): array
    {
        $options = ['' => '-- ' . e(trans('creativesizzle.redirect::lang.redirect.none')) . ' --' ];

        $hasPagesPlugin = PluginManager::instance()->hasPlugin('RainLab.Pages');

        if (! $hasPagesPlugin) {
            return $options;
        }

        $pages = \RainLab\Pages\Classes\Page::listInTheme(Theme::getActiveTheme());

        /** @var \RainLab\Pages\Classes\Page $page */
        foreach ($pages as $page) {
            if (array_key_exists('title', $page->viewBag)) {
                $options[$page->getBaseFileName()] = $page->viewBag['title'];
            }
        }

        return $options;
    }

    public static function getCategoryOptions(): array
    {
        return Category::query()
            ->get(['id', 'name'])
            ->pluck('name', 'key')
            ->toArray();
    }
}
