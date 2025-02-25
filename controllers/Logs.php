<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Controllers;

use Backend\Behaviors\ListController;
use Backend\Classes\Controller;
use BackendMenu;
use CreativeSizzle\Redirect\Models\RedirectLog;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Throwable;
use Winter\Storm\Flash\FlashBag;

/**
 * @mixin ListController
 */
final class Logs extends Controller
{
    public $implement = [
        ListController::class,
    ];

    public $requiredPermissions = ['creativesizzle.redirect.access_redirects'];

    public string $listConfig = 'config_list.yaml';

    private Request $request;

    private Translator $translator;

    private FlashBag $flash;

    private LoggerInterface $log;

    public function __construct(Request $request, Translator $translator, LoggerInterface $log)
    {
        parent::__construct();

        BackendMenu::setContext('CreativeSizzle.Redirect', 'redirect', 'logs');

        $this->addCss('/plugins/creativesizzle/redirect/assets/dist/css/redirect.css', 'CreativeSizzle.Redirect');

        $this->request = $request;
        $this->translator = $translator;
        $this->flash = resolve('flash');
        $this->log = $log;
    }

    public function onRefresh(): array
    {
        return $this->listRefresh();
    }

    public function onEmptyLog(): array
    {
        try {
            RedirectLog::query()->truncate();
            $this->flash->success($this->translator->trans('creativesizzle.redirect::lang.flash.truncate_success'));
        } catch (Throwable $e) {
            $this->log->warning($e);
        }

        return $this->listRefresh();
    }

    public function onDelete(): array
    {
        if (($checkedIds = $this->request->get('checked', []))
            && is_array($checkedIds)
            && count($checkedIds)
        ) {
            foreach ($checkedIds as $recordId) {
                try {
                    /** @var RedirectLog $record */
                    $record = RedirectLog::query()->findOrFail($recordId);
                    $record->delete();
                } catch (Throwable $e) {
                    $this->log->warning($e);
                }
            }

            $this->flash->success($this->translator->trans('creativesizzle.redirect::lang.flash.delete_selected_success'));
        } else {
            $this->flash->error($this->translator->trans('backend::lang.list.delete_selected_empty'));
        }

        return $this->listRefresh();
    }
}
