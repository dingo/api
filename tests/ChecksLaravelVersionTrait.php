<?php

namespace Dingo\Api\Tests;

use Dingo\Api\Tests\Stubs\ApplicationStub;
use Dingo\Api\Tests\Stubs\Application6Stub;
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

        // Remove the 'v' in for example 'v5.8.3'
        $version = str_replace('v', '', $version);

        // Return the version stub for the right version
        if (version_compare($version, '6.0.0', '>=')) {
            return new Application6Stub;
        } elseif (version_compare($version, '5.8.0', '>=')) {
            return new Application58Stub;
        } else {
            return new ApplicationStub;
        }
    }
}
