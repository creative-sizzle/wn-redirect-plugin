<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use Backend\Models\BrandSetting;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use CreativeSizzle\Redirect\Classes\StatisticsHelper;
use Illuminate\Http\JsonResponse;

/**
 * @property string $pageTitle
 */
final class Statistics extends Controller
{
    public $requiredPermissions = ['creativesizzle.redirect.access_redirects'];

    private StatisticsHelper $helper;

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('CreativeSizzle.Redirect', 'redirect', 'statistics');

        $this->pageTitle = 'creativesizzle.redirect::lang.title.statistics';

        $this->addCss('/plugins/creativesizzle/redirect/assets/dist/css/redirect.css', 'CreativeSizzle.Redirect');
        $this->addCss('/plugins/creativesizzle/redirect/assets/dist/css/statistics.css', 'CreativeSizzle.Redirect');

        $this->addJs('/plugins/creativesizzle/redirect/assets/dist/js/statistics.js', 'CreativeSizzle.Redirect');

        $this->helper = new StatisticsHelper();
    }

    public function index(): void
    {
        // Chart
        $this->vars['monthYearOptions'] = $this->helper->getMonthYearOptions();
        $this->vars['monthYearSelected'] = today()->month.'_'.today()->year;

        // Cards
        $this->vars['topTenRedirectsThisMonth'] = $this->helper->getTopRedirectsThisMonth();
        $this->vars['topTenCrawlersThisMonth'] = $this->helper->getTopTenCrawlersThisMonth();

        // Scoreboard
        $this->vars['redirectHitsPerMonth'] = $this->helper->getRedirectHitsPerMonth();
        $this->vars['totalActiveRedirects'] = $this->helper->getTotalActiveRedirects();
        $this->vars['activeRedirects'] = $this->helper->getActiveRedirects();
        $this->vars['totalRedirectsServed'] = $this->helper->getTotalRedirectsServed();
        $this->vars['totalThisMonth'] = $this->helper->getTotalThisMonth();
        $this->vars['totalLastMonth'] = $this->helper->getTotalLastMonth();
        $this->vars['latestClient'] = $this->helper->getLatestClient();
    }

    public function onLoadHitsPerDay(): JsonResponse
    {
        $today = Carbon::today();

        $postValue = post('period_month_year', $today->month . '_' . $today->year);

        [$month, $year] = array_map(fn ($value) => (int) $value, explode('_', $postValue));

        return response()->json([
            'datasets' => [
                $this->getHitsPerDayAsDataSet($month, $year, true),
                $this->getHitsPerDayAsDataSet($month, $year, false),
            ],
            'labels' => $this->getLabels($month, $year),
        ]);
    }

    private function getLabels(int $month, int $year): array
    {
        $labels = [];

        $date = Carbon::createFromDate($year, $month)->toImmutable();

        foreach ($date->firstOfMonth()->daysUntil($date->endOfMonth()) as $day) {
            $labels[] = $day->isoFormat('LL');
        }

        return $labels;
    }

    /**
     * @throws InvalidFormatException
     */
    private function getHitsPerDayAsDataSet(int $month, int $year, bool $crawler): array
    {
        $today = Carbon::createFromDate($year, $month, 1);

        $data = $this->helper->getRedirectHitsPerDay($month, $year, $crawler);

        for ($i = $today->firstOfMonth()->day; $i <= $today->lastOfMonth()->day; $i++) {
            if (! array_key_exists($i, $data)) {
                $data[$i] = ['hits' => 0];
            }
        }

        ksort($data);

        $brandSettings = new BrandSetting();

        $color = $crawler
            ? $brandSettings->get('primary_color')
            : $brandSettings->get('secondary_color');

        [$r, $g, $b] = sscanf($color, "#%02x%02x%02x");

        return [
            'label' => $crawler
                ? e(trans('creativesizzle.redirect::lang.statistics.crawler_hits'))
                : e(trans('creativesizzle.redirect::lang.statistics.visitor_hits')),
            'backgroundColor' => sprintf('rgb(%d, %d, %d, 0.5)', $r, $g, $b),
            'borderColor' => sprintf('rgb(%d, %d, %d, 1)', $r, $g, $b),
            'borderWidth' => 1,
            'data' => data_get($data, '*.hits'),
        ];
    }
}
