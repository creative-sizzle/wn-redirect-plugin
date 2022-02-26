<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Tests;

use System\Classes\PluginManager;

class TestCase extends \PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $pluginManager = PluginManager::instance();

        $pluginList = array_keys($pluginManager->getPlugins());
        foreach ($pluginList as $pluginKey) {
            if (! preg_match('%^creativesizzle.*%i', $pluginKey)) {
                continue;
            }

            $pluginManager->refreshPlugin($pluginKey);
        }

        $pluginManager->registerAll(true);
        $pluginManager->bootAll(true);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $pluginManager = PluginManager::instance();
        $pluginManager->unregisterAll();
    }
}
