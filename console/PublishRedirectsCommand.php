<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Console;

use Illuminate\Console\Command;
use CreativeSizzle\Redirect\Classes\PublishManager;

final class PublishRedirectsCommand extends Command
{
    public function __construct()
    {
        $this->name = 'creativesizzle:redirect:publish-redirects';
        $this->description = 'Publish all redirects.';

        parent::__construct();
    }

    public function handle(PublishManager $publishManager): void
    {
        $publishManager->publish();
    }
}
