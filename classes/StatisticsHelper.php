<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes;

use Carbon\Carbon;
use CreativeSizzle\Redirect\Classes\Observers\RedirectObserver;
use CreativeSizzle\Redirect\Models;
use Illuminate\Support\Facades\DB;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Winter\Storm\Database\Collection;

final class StatisticsHelper
{
    public function getTotalRedirectsServed(): int
    {
        return Models\Client::query()->count();
    }

    public function getLatestClient(?int $redirectId = null): ?Models\Client
    {
        $builder = Models\Client::query()
            ->latest('timestamp');

        if ($redirectId !== null) {
            $builder->where('redirect_id', '=', $redirectId);
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $builder->first();
    }

    /**
     * Fetch the number of redirects for the month (defaults to current month).
     *
     * @param  int|null  $redirectId
     * @param  int|null  $month
     * @param  int|null  $year
     * @return int
     */
    public function getTotalForMonth(int $month, int $year, ?int $redirectId = null): int
    {
        if (! checkdate($month, 1, $year)) {
            throw new \InvalidArgumentException('The supplied month and/or year is not a valid date.');
        }

        $builder = Models\Client::query()
            ->where('month', $month)
            ->where('year', $year);

        if ($redirectId !== null) {
            $builder->where('redirect_id', '=', $redirectId);
        }

        return $builder->count();
    }

    public function getActiveRedirects(): array
    {
        $groupedRedirects = [];

        /** @var Collection $redirects */
        $redirects = Models\Redirect::enabled()
            ->get()
            ->filter(static function (Models\Redirect $redirect): bool {
                return $redirect->isActiveOnDate(Carbon::today());
            });

        /** @var Models\Redirect $redirect */
        foreach ($redirects as $redirect) {
            $groupedRedirects[$redirect->getAttribute('status_code')][] = $redirect;
        }

        return $groupedRedirects;
    }

    public function getTotalActiveRedirects(): int
    {
        return Models\Redirect::enabled()
            ->get()
            ->filter(static function (Models\Redirect $redirect): bool {
                return $redirect->isActiveOnDate(today());
            })
            ->count();
    }

    public function getMonthYearOptions(): array
    {
        $result = Models\Client::query()
            ->addSelect('month', 'year')
            ->groupBy('month', 'year')
            ->orderByRaw('year DESC, month DESC');

        $data = $result->get()
            ->toArray();

        $options = [];

        foreach ($data as $monthYear) {
            $options[$monthYear['month'].'_'.$monthYear['year']]
                = Carbon::createFromDate($monthYear['year'], $monthYear['month'])->isoFormat('MMMM Y');
        }

        return $options;
    }

    public function getRedirectHitsPerDay(int $month, int $year, bool $crawler = false): array
    {
        $result = Models\Client::query()
            ->selectRaw('COUNT(id) AS hits')
            ->where('month', $month)
            ->where('year', $year)
            ->addSelect('day', 'month', 'year')
            ->groupBy('day', 'month', 'year')
            ->orderByRaw('year DESC, month DESC, day DESC');

        if ($crawler) {
            $result->whereNotNull('crawler');
        } else {
            $result->whereNull('crawler');
        }

        return $result->get()
            ->keyBy('day')
            ->toArray();
    }

    public function getRedirectHitsSparkline(int $redirectId, bool $crawler, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        // DB index: redirect_timestamp_crawler
        $builder = Models\Client::query()
            ->selectRaw('COUNT(id) AS hits, DATE(timestamp) AS date')
            ->where('redirect_id', '=', $redirectId)
            ->groupBy('day', 'month', 'year', 'timestamp')
            ->orderByRaw('year ASC, month ASC, day ASC')
            ->where('timestamp', '>=', $startDate->toDateTimeString());

        if ($crawler) {
            $builder->whereNotNull('crawler');
        } else {
            $builder->whereNull('crawler');
        }

        $result = $builder
            ->get()
            ->keyBy('date')
            ->toArray();

        $hits = [];

        while ($startDate->lt(Carbon::now())) {
            if (isset($result[$startDate->toDateString()])) {
                $hits[] = (int) $result[$startDate->toDateString()]['hits'];
            } else {
                $hits[] = 0;
            }

            $startDate->addDay();
        }

        return $hits;
    }

    public function getRedirectHitsPerMonth(): array
    {
        return Models\Client::query()
            ->selectRaw('COUNT(id) AS hits')
            ->addSelect('month', 'year')
            ->groupBy('month', 'year')
            ->orderByRaw('year DESC, month DESC')
            ->limit(12)
            ->get()
            ->toArray();
    }

    public function getTopTenCrawlersForMonth(int $month, int $year): array
    {
        if (! checkdate($month, 1, $year)) {
            throw new \InvalidArgumentException();
        }

        // DB index: month_year_crawler
        return Models\Client::query()
            ->selectRaw('COUNT(id) AS hits')
            ->addSelect('crawler')
            ->where('month', '=', $month)
            ->where('year', '=', $year)
            ->whereNotNull('crawler')
            ->groupBy('crawler')
            ->orderByRaw('hits DESC')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function getTopRedirectsForMonth(int $month, int $year, int $limit = 10): array
    {
        if (! checkdate($month, 1, $year)) {
            throw new \InvalidArgumentException();
        }

        return Models\Client::query()
            ->selectRaw('COUNT(redirect_id) AS hits')
            ->addSelect('redirect_id', 'r.from_url')
            ->join('creativesizzle_redirect_redirects AS r', 'r.id', '=', 'redirect_id')
            ->where('month', '=', $month)
            ->where('year', '=', $year)
            ->groupBy('redirect_id', 'r.from_url')
            ->orderByRaw('hits DESC')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function increaseHitsForRedirect(int $redirectId): void
    {
        /** @var ?Models\Redirect $redirect */
        $redirect = Models\Redirect::query()->find($redirectId);

        if ($redirect === null) {
            return;
        }

        $now = Carbon::now();

        RedirectObserver::stopHandleChanges();

        /** @noinspection PhpUndefinedClassInspection */
        $redirect->forceFill(['hits' => DB::raw('hits + 1'), 'last_used_at' => $now]);
        $redirect->forceSave();

        RedirectObserver::startHandleChanges();

        $crawlerDetect = new CrawlerDetect();

        Models\Client::create([
            'redirect_id' => $redirectId,
            'timestamp' => $now,
            'day' => $now->day,
            'month' => $now->month,
            'year' => $now->year,
            'crawler' => $crawlerDetect->isCrawler() ? $crawlerDetect->getMatches() : null,
        ]);
    }
}
