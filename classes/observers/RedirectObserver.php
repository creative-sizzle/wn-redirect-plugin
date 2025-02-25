<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes\Observers;

use CreativeSizzle\Redirect\Classes\Observers\Traits\CanBeDisabled;
use CreativeSizzle\Redirect\Models;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

final class RedirectObserver
{
    use CanBeDisabled;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    public function __construct(
        Dispatcher $dispatcher,
        LoggerInterface $log
    ) {
        $this->dispatcher = $dispatcher;
        $this->log = $log;
    }

    /**
     * @param  Models\Redirect  $model
     * @return void
     */
    public function created(Models\Redirect $model): void
    {
        if (! self::canHandleChanges()) {
            return;
        }

        $this->logChange($model, 'created');

        $this->dispatcher->dispatch('creativesizzle.redirect.changed', [
            'redirectIds' => Arr::wrap($model->getKey()),
        ]);
    }

    /**
     * @param  Models\Redirect  $model
     * @return void
     */
    public function updated(Models\Redirect $model): void
    {
        if (! self::canHandleChanges()) {
            return;
        }

        $this->logChange($model, 'updated');

        $this->dispatcher->dispatch('creativesizzle.redirect.changed', [
            'redirectIds' => Arr::wrap($model->getKey()),
        ]);
    }

    /**
     * @param  Models\Redirect  $model
     * @return void
     */
    public function deleted(Models\Redirect $model): void
    {
        if (! self::canHandleChanges()) {
            return;
        }

        $this->logChange($model, 'deleted');

        $this->dispatcher->dispatch('creativesizzle.redirect.changed', [
            'redirectIds' => Arr::wrap($model->getKey()),
        ]);
    }

    private function logChange(Models\Redirect $model, string $typeOfChange): void
    {
        if ((bool) config('creativesizzle.redirect::log_redirect_changes', false) === false) {
            return;
        }

        $this->log->info(sprintf(
            'CreativeSizzle.Redirect: Redirect %d has been %s.',
            $model->getKey(),
            $typeOfChange
        ), $model->getDirty());
    }
}
