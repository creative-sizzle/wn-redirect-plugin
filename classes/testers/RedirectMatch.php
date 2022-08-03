<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes\Testers;

use Backend\Facades\Backend;
use CreativeSizzle\Redirect\Classes\Exceptions\InvalidScheme;
use CreativeSizzle\Redirect\Classes\Exceptions\NoMatchForRequest;
use CreativeSizzle\Redirect\Classes\TesterBase;
use CreativeSizzle\Redirect\Classes\TesterResult;
use CreativeSizzle\Redirect\Models\Redirect;

final class RedirectMatch extends TesterBase
{
    protected function test(): TesterResult
    {
        $manager = $this->getRedirectManager();

        try {
            $match = $manager->match(
                $this->testPath,
                $this->secure ? Redirect::SCHEME_HTTPS : Redirect::SCHEME_HTTP
            );
        } catch (NoMatchForRequest | InvalidScheme $e) {
            $match = false;
        }

        if ($match === false) {
            return new TesterResult(false, e(trans('creativesizzle.redirect::lang.test_lab.not_match_redirect')));
        }

        $message = sprintf(
            '%s <a href="%s" target="_blank">%s</a>.',
            e(trans('creativesizzle.redirect::lang.test_lab.matched')),
            Backend::url('creativesizzle/redirect/redirects/update/'.$match->getId()),
            e(trans('creativesizzle.redirect::lang.test_lab.redirect'))
        );

        return new TesterResult(true, $message);
    }
}
