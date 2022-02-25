<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes\Testers;

use CreativeSizzle\Redirect\Classes\TesterBase;
use CreativeSizzle\Redirect\Classes\TesterResult;
use InvalidArgumentException;

final class RedirectFinalDestination extends TesterBase
{
    /**
     * @throws InvalidArgumentException
     */
    protected function test(): TesterResult
    {
        $curlHandle = curl_init($this->testUrl);

        $this->setDefaultCurlOptions($curlHandle);

        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, false);

        $error = null;

        if (curl_exec($curlHandle) === false) {
            $error = e(trans('creativesizzle.redirect::lang.test_lab.not_determinate_destination_url'));
        }

        $finalDestination = curl_getinfo($curlHandle, CURLINFO_REDIRECT_URL);
        $statusCode = (int) curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

        curl_close($curlHandle);

        if (empty($finalDestination) && $statusCode > 400) {
            $message = $error ?? e(trans('creativesizzle.redirect::lang.test_lab.no_destination_url'));
        } else {
            $finalDestination = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                e($finalDestination),
                e($finalDestination)
            );

            $message = $error
                ?? trans('creativesizzle.redirect::lang.test_lab.final_destination_is', ['destination' => $finalDestination]);
        }

        return new TesterResult($error === null, $message);
    }
}
