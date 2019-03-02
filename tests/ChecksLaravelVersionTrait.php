<?php

namespace Dingo\Api\Tests;

use Dingo\Api\Tests\Stubs\ApplicationStub;
use Dingo\Api\Tests\Stubs\Application58Stub;

trait ChecksLaravelVersionTrait
{
    public $installed_file_path = __DIR__.'/../vendor/composer/installed.json';
    public $current_release = '5.8';

    private function getFrameworkVersion()
    {
        $contents = file_get_contents($this->installed_file_path);
        $parsed_data = json_decode($contents, true);
        $just_laravel = array_filter($parsed_data, function ($val) {
            if ('laravel/framework' === $val['name'] || 'laravel/lumen-framework' === $val['name']) {
                return true;
            }
        });
        $laravelVersion = array_map(function ($val) {
            return $val['version'];
        }, array_values($just_laravel))[0];

        return $laravelVersion;
    }

    private function getApplicationStub()
    {
        $version = $this->getFrameworkVersion();
        if ('dev-master' === $version) {
            $version = $this->current_release;
        }
        $compared_versions = version_compare('5.8', $version);
        // If comparison is Less Than, or Equal To, provide the 5.8 stub.
        if ($compared_versions === -1 || $compared_versions === 0) {
            return new Application58Stub;
        }

        return new ApplicationStub;
    }
}
