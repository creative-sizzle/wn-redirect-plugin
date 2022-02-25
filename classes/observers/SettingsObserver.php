<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes\Observers;

use Throwable;
use CreativeSizzle\Redirect\Classes\Contracts\PublishManagerInterface;

final class SettingsObserver
{
    private $publishManager;

    public function __construct(PublishManagerInterface $publishManager)
    {
        $this->publishManager = $publishManager;
    }

    public function saving(): void
    {
        try {
            $this->publishManager->publish();
        } catch (Throwable $e) {
            // ..
        }
    }
}
