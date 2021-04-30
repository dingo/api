<?php

namespace Dingo\Api\Tests;

use Dingo\Api\Tests\Stubs\ApplicationStub;
use Dingo\Api\Tests\Stubs\Application8Stub;
use Dingo\Api\Tests\Stubs\Application7Stub;
use Dingo\Api\Tests\Stubs\Application6Stub;
use Dingo\Api\Tests\Stubs\Application58Stub;

trait ChecksLaravelVersionTrait
{
    public $installed_file_path = __DIR__.'/../vendor/composer/installed.json';
    public $current_release = '8.0';

    private function getFrameworkVersion()
    {
        $contents = file_get_contents($this->installed_file_path);
        $parsed_data = json_decode($contents, true);

        // Changed array format in newer versions of composer (v2?)
        if (array_key_exists('packages', $parsed_data)) {
            $parsed_data = $parsed_data['packages'];
        }

        // Find laravel/framework or lumen package
        $just_laravel = array_filter($parsed_data, function ($composerPackageData) {
            if (is_array($composerPackageData) && array_key_exists('name', $composerPackageData)) {
                if ('laravel/framework' === $composerPackageData['name'] || 'laravel/lumen-framework' === $composerPackageData['name']) {
                    return true;
                }
            }
        });

        if (empty($just_laravel)) {
            exit(PHP_EOL.'No Laravel version detected, please do a "composer require laravel/framework:x" prior to testing'.PHP_EOL);
        }

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
        if (version_compare($version, '8.0.0', '>=')) {
            return new Application8Stub;
        } elseif (version_compare($version, '7.0.0', '>=')) {
            return new Application7Stub;
        } elseif (version_compare($version, '6.0.0', '>=')) {
            return new Application6Stub;
        } elseif (version_compare($version, '5.8.0', '>=')) {
            return new Application58Stub;
        } else {
            return new ApplicationStub;
        }
    }
}
