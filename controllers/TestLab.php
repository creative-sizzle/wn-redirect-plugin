<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use Carbon\Carbon;
use CreativeSizzle\Redirect\Classes\Testers;
use CreativeSizzle\Redirect\Models\Redirect;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Throwable;
use Winter\Storm\Database\Collection;
use Winter\Storm\Flash\FlashBag;

/**
 * @property string $bodyClass
 */
final class TestLab extends Controller
{
    public $requiredPermissions = ['creativesizzle.redirect.access_redirects'];

    private array $redirects = [];

    private Request $request;

    private Translator $translator;

    private FlashBag $flash;

    public function __construct(Request $request, Translator $translator)
    {
        $this->bodyClass = 'layout-relative';

        parent::__construct();

        BackendMenu::setContext('CreativeSizzle.Redirect', 'redirect', 'test-lab');

        $this->loadRedirects();

        $this->request = $request;
        $this->translator = $translator;
        $this->flash = resolve('flash');
    }

    public function index(): void
    {
        $this->pageTitle = 'creativesizzle.redirect::lang.title.test_lab';

        $this->addCss('/plugins/creativesizzle/redirect/assets/dist/css/redirect.css', 'CreativeSizzle.Redirect');
        $this->addCss('/plugins/creativesizzle/redirect/assets/dist/css/test-lab.css', 'CreativeSizzle.Redirect');
        $this->addJs('/plugins/creativesizzle/redirect/assets/dist/js/test-lab.js', 'CreativeSizzle.Redirect');

        $this->vars['redirectCount'] = $this->getRedirectCount();
    }

    private function loadRedirects(): void
    {
        /** @var Collection $redirects */
        $this->redirects = array_values(Redirect::enabled()
            ->testLabEnabled()
            ->orderBy('sort_order')
            ->get()
            ->filter(static function (Redirect $redirect): bool {
                return $redirect->isActiveOnDate(Carbon::today());
            })
            ->all());
    }

    private function offsetGetRedirect(int $offset): ?Redirect
    {
        return $this->redirects[$offset] ?? null;
    }

    public function onTest(): string
    {
        $offset = (int) $this->request->get('offset');

        $redirect = $this->offsetGetRedirect($offset);

        if ($redirect === null) {
            return '';
        }

        try {
            $partial = (string) $this->makePartial('tester_result', [
                'redirect' => $redirect,
                'testPath' => $this->getTestPath($redirect),
                'testResults' => $this->getTestResults($redirect),
            ]);
        } catch (Throwable $e) {
            $partial = (string) $this->makePartial('tester_failed', [
                'redirect' => $redirect,
                'message' => $e->getMessage(),
            ]);
        }

        return $partial;
    }

    /**
     * @throws ModelNotFoundException
     */
    public function onReRun(): array
    {
        /** @var Redirect $redirect */
        $redirect = Redirect::query()->findOrFail($this->request->get('id'));

        $this->flash->success(trans('creativesizzle.redirect::lang.test_lab.flash_test_executed'));

        return [
            '#testerResult' . $redirect->getKey() => $this->makePartial(
                'tester_result_items',
                $this->getTestResults($redirect)
            ),
        ];
    }

    /**
     * @throws ModelNotFoundException
     */
    public function onExclude(): array
    {
        /** @var Redirect $redirect */
        $redirect = Redirect::query()->findOrFail($this->request->get('id'));
        $redirect->update(['test_lab' => false]);

        $this->flash->success(trans('creativesizzle.redirect::lang.test_lab.flash_redirect_excluded'));

        return [
            '#testButtonWrapper' => $this->makePartial('test_button', [
                'redirectCount' => $this->getRedirectCount(),
            ]),
        ];
    }

    public function getTestPath(Redirect $redirect): string
    {
        $testPath = '/';

        if ($redirect->isMatchTypeExact()) {
            $testPath = (string) $redirect->getAttribute('from_url');
        } elseif ($redirect->getAttribute('test_lab_path')) {
            $testPath = (string) $redirect->getAttribute('test_lab_path');
        }

        return $testPath;
    }

    public function getTestResults(Redirect $redirect): array
    {
        $testPath = $this->getTestPath($redirect);

        return [
            'maxRedirectsResult' => (new Testers\RedirectLoop($testPath))->execute(),
            'matchedRedirectResult' => (new Testers\RedirectMatch($testPath))->execute(),
            'responseCodeResult' => (new Testers\ResponseCode($testPath))->execute(),
            'redirectCountResult' => (new Testers\RedirectCount($testPath))->execute(),
            'finalDestinationResult' => (new Testers\RedirectFinalDestination($testPath))->execute(),
        ];
    }

    private function getRedirectCount(): int
    {
        return count($this->redirects);
    }
}
