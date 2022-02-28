<?php

declare(strict_types=1);

use Backend\Facades\BackendAuth;
use Backend\Models\BrandSetting;
use CreativeSizzle\Redirect\Classes\Sparkline;
use CreativeSizzle\Redirect\Classes\StatisticsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web'],
    'as' => 'creativesizzle.redirect.',
], static function () {
    // TODO: Extract this into a controller.
    Route::get('creativesizzle/redirect/sparkline/{redirectId}', static function ($redirectId) {
        if (! BackendAuth::check() || ! BackendAuth::getUser()->hasAccess('creativesizzle.redirect.access_redirect')) {
            return response()->make(\Illuminate\Support\Facades\Lang::get('backend::lang.page.access_denied.label'), 403);
        }

        /** @var Request $request */
        $request = resolve(Request::class);

        $preset = $request->get('preset', '30d-small');

        $properties = [
            'format' => '200x60',
            'lineThickness' => 3,
            'days' => 30,
        ];

        if ($preset === '3m-large') {
            $properties = [
                'format' => '520x120',
                'lineThickness' => 2,
                'days' => 90,
            ];
        }

        $cacheKey = sprintf('creativesizzle_redirect_%s_%d', $preset, (int)$redirectId);

        $data = Cache::remember($cacheKey, 5 * 60, static function () use ($redirectId, $properties) {
            return (new StatisticsHelper())->getRedirectHitsSparkline((int)$redirectId, false, $properties['days']);
        });

        $crawlerData = Cache::remember($cacheKey . '_crawler', 5 * 60, static function () use ($redirectId, $properties) {
            return (new StatisticsHelper())->getRedirectHitsSparkline((int)$redirectId, true, $properties['days']);
        });

        // TODO: Generate fallback image data if generating image fails.
        $imageData = Cache::remember($cacheKey . '_image', 5 * 60, static function () use ($data, $crawlerData, $properties) {
            $primaryColor = BrandSetting::get('primary_color', BrandSetting::PRIMARY_COLOR);
            $secondaryColor = BrandSetting::get('secondary_color', BrandSetting::SECONDARY_COLOR);

            $sparkline = new Sparkline();
            $sparkline->setFormat($properties['format']);
            $sparkline->setPadding('2 0 0 2');
            $sparkline->setData($crawlerData, $data);
            $sparkline->setLineThickness($properties['lineThickness']);

            $sparkline->setFillColorHex($primaryColor, 0);
            $sparkline->setFillColorHex($secondaryColor, 1);

            $sparkline->setLineColorHex($primaryColor, 0);
            $sparkline->setLineColorHex($secondaryColor, 1);

            $sparkline->deactivateBackgroundColor();

            return $sparkline->toBase64();
        });

        return response()
            ->make(base64_decode($imageData), 200, [
                'Content-Type' => 'image/png',
                'Expires' => now()->addDay()->toRfc7231String(),
                'Content-Disposition' => 'inline; filename=' . $cacheKey . '.png',
                'Accept-Ranges' => 'none',
            ]);
    })->where(['redirectId' => '[0-9]+'])
        ->name('sparkline');
});
