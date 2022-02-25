<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes\Testers;

use CreativeSizzle\Redirect\Classes\Exceptions\InvalidScheme;
use CreativeSizzle\Redirect\Classes\Exceptions\NoMatchForRequest;
use CreativeSizzle\Redirect\Classes\TesterBase;
use CreativeSizzle\Redirect\Classes\TesterResult;
use CreativeSizzle\Redirect\Models\Redirect;
use InvalidArgumentException;
use Request;

/**
 * Tester for checking if the response HTTP code is equal to the matched redirect.
 *
 * Situations:
 * a) Failing when given path matches a redirect but response code is not equal to response code.
 * b) Failing when given path does not match but status code is not 301, 302, ...
 * c) Passes when given path does not match with a redirect.
 */
final class ResponseCode extends TesterBase
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
            $error = curl_error($curlHandle);
        }

        if ($error !== null) {
            return new TesterResult(false, e(trans('creativesizzle.redirect::lang.test_lab.result_request_failed')));
        }

        $statusCode = (int) curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

        curl_close($curlHandle);

        $manager = $this->getRedirectManager();

        // TODO: Add scheme
        try {
            $match = $manager->match($this->testPath, Request::getScheme());
        } catch (NoMatchForRequest | InvalidScheme $e) {
            $match = false;
        }

        if ($match && $match->getStatusCode() !== $statusCode) {
            $message = e(trans('creativesizzle.redirect::lang.test_lab.matched_not_http_code', [
                'expected' => $match->getStatusCode(),
                'received' => $statusCode,
            ]));

            return new TesterResult(false, $message);
        }

        if ($match && $match->getStatusCode() === $statusCode) {
            $message = e(trans('creativesizzle.redirect::lang.test_lab.matched_http_code', [
                'code' => $statusCode,
            ]));

            return new TesterResult(true, $message);
        }

        // Should be a 301, 302, 303, 404, 410, ...
        if (! array_key_exists($statusCode, Redirect::$statusCodes)) {
            return new TesterResult(
                false,
                e(trans('creativesizzle.redirect::lang.test_lab.response_http_code_should_be'))
                . ' '
                . implode(', ', array_keys(Redirect::$statusCodes))
            );
        }

        return new TesterResult(
            true,
            e(trans('creativesizzle.redirect::lang.test_lab.response_http_code')) . ': ' . $statusCode
        );
    }
}
