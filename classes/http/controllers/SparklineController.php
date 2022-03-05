<?php

namespace CreativeSizzle\Redirect\Classes\Http\Controllers;

use Backend\Facades\BackendAuth;
use Backend\Models\BrandSetting;
use CreativeSizzle\Redirect\Classes\Sparkline;
use CreativeSizzle\Redirect\Classes\StatisticsHelper;
use Illuminate\Routing\Controller;

class SparklineController extends Controller
{
    public function __invoke($redirectId)
    {
        if (! BackendAuth::check() || ! BackendAuth::getUser()->hasAccess('creativesizzle.redirect.access_redirect')) {
            return response()->make(trans('backend::lang.page.access_denied.label'), 403);
        }

        $preset = request()->get('preset', '30d-small');

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

        $data = cache()->remember($cacheKey, 5 * 60, static function () use ($redirectId, $properties) {
            return (new StatisticsHelper())->getRedirectHitsSparkline($redirectId, false, $properties['days']);
        });

        $crawlerData = cache()->remember($cacheKey . '_crawler', 5 * 60, static function () use ($redirectId, $properties) {
            return (new StatisticsHelper())->getRedirectHitsSparkline($redirectId, true, $properties['days']);
        });

        // TODO: Generate fallback image data if generating image fails.
        $imageData = cache()->remember($cacheKey . '_image', 5 * 60, static function () use ($data, $crawlerData, $properties) {
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
    }
}
