<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Tests;

use CreativeSizzle\Redirect\Classes\OptionHelper;
use CreativeSizzle\Redirect\Models\Redirect;
use PHPUnit_Framework_Exception;
use System\Tests\Bootstrap\PluginTestCase;

class OptionHelperTest extends PluginTestCase
{
    /**
     * @throws PHPUnit_Framework_Exception
     */
    public function testTargetTypeOptions(): void
    {
        self::assertCount(1, OptionHelper::getTargetTypeOptions(404));
        self::assertCount(3, OptionHelper::getTargetTypeOptions(301));

        self::assertArrayHasKey(Redirect::TARGET_TYPE_NONE, OptionHelper::getTargetTypeOptions(404));
        self::assertArrayHasKey(Redirect::TARGET_TYPE_NONE, OptionHelper::getTargetTypeOptions(410));

        self::assertArrayHasKey(Redirect::TARGET_TYPE_PATH_URL, OptionHelper::getTargetTypeOptions(301));
        self::assertArrayHasKey(Redirect::TARGET_TYPE_CMS_PAGE, OptionHelper::getTargetTypeOptions(301));
        self::assertArrayHasKey(Redirect::TARGET_TYPE_STATIC_PAGE, OptionHelper::getTargetTypeOptions(301));

        self::assertArrayHasKey(Redirect::TARGET_TYPE_PATH_URL, OptionHelper::getTargetTypeOptions(302));
        self::assertArrayHasKey(Redirect::TARGET_TYPE_CMS_PAGE, OptionHelper::getTargetTypeOptions(302));
        self::assertArrayHasKey(Redirect::TARGET_TYPE_STATIC_PAGE, OptionHelper::getTargetTypeOptions(302));

        self::assertArrayHasKey(Redirect::TARGET_TYPE_PATH_URL, OptionHelper::getTargetTypeOptions(303));
        self::assertArrayHasKey(Redirect::TARGET_TYPE_CMS_PAGE, OptionHelper::getTargetTypeOptions(303));
        self::assertArrayHasKey(Redirect::TARGET_TYPE_STATIC_PAGE, OptionHelper::getTargetTypeOptions(303));
    }
}
