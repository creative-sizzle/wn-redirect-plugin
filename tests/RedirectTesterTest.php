<?php

namespace CreativeSizzle\Redirect\Tests;

use CreativeSizzle\Redirect\Classes\TesterBase;
use CreativeSizzle\Redirect\Classes\TesterResult;

class RedirectTesterTest extends \PluginTestCase
{
    public function test_it_can_accept_curl_resource_or_handle()
    {
        $tester = new RedirectTester('/foo');

        $this->assertTrue($tester->execute()->isPassed());
    }
}

class RedirectTester extends TesterBase
{
    protected function test(): TesterResult
    {
        $curlHandle = curl_init($this->testUrl);

        $this->setDefaultCurlOptions($curlHandle);

        return new TesterResult(true, 'Success');
    }
}
