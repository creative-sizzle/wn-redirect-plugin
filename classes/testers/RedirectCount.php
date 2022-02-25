<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes\Testers;

use InvalidArgumentException;
use CreativeSizzle\Redirect\Classes\TesterBase;
use CreativeSizzle\Redirect\Classes\TesterResult;

final class RedirectCount extends TesterBase
{
    /**
     * @throws InvalidArgumentException
     */
    protected function test(): TesterResult
    {
        $curlHandle = curl_init($this->testUrl);

        $this->setDefaultCurlOptions($curlHandle);

        $error = null;

        if (curl_exec($curlHandle) === false) {
            $error = curl_error($curlHandle);
        }

        if ($error !== null) {
            return new TesterResult(false, e(trans('creativesizzle.redirect::lang.test_lab.result_request_failed')));
        }

        $statusCode = (int) curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $redirectCount = (int) curl_getinfo($curlHandle, CURLINFO_REDIRECT_COUNT);

        curl_close($curlHandle);

        return new TesterResult(
            $redirectCount === 1 || ($redirectCount === 0 && $statusCode > 400),
            e(trans('creativesizzle.redirect::lang.test_lab.redirects_followed', ['count' => $redirectCount, 'limit' => 10]))
        );
    }
}
