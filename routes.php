<?php

declare(strict_types=1);

use CreativeSizzle\Redirect\Classes\Http\Controllers\SparklineController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web'],
    'as' => 'creativesizzle.redirect.',
], static function () {
    Route::get('creativesizzle/redirect/sparkline/{redirectId}', SparklineController::class)
        ->where(['redirectId' => '[0-9]+'])
        ->name('sparkline');
});
