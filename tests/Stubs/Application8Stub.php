<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

class Application8Stub extends Container implements Application
{
    public function version()
    {
        return 'v1';
    }

    public function basePath($path = '')
    {
        //
    }

    public function bootstrapPath($path = '')
    {
        //
    }

    public function configPath($path = '')
    {
        //
    }

    public function databasePath($path = '')
    {
        //
    }

    public function environmentPath()
    {
        //
    }

    public function resourcePath($path = '')
    {
        //
    }

    public function storagePath()
    {
        //
    }

    public function environment(...$environments)
    {
        return 'testing';
    }

    public function runningInConsole()
    {
        //
    }

    public function runningUnitTests()
    {
        // TODO: Implement runningUnitTests() method.
    }

    public function isDownForMaintenance()
    {
        return false;
    }

    public function registerConfiguredProviders()
    {
        //
    }

    public function register($provider, $options = [], $force = false)
    {
        //
    }

    public function registerDeferredProvider($provider, $service = null)
    {
        //
    }

    public function resolveProvider($provider)
    {
        //
    }

    public function boot()
    {
        //
    }

    public function booting($callback)
    {
        //
    }

    public function booted($callback)
    {
        //
    }

    public function bootstrapWith(array $bootstrappers)
    {
        //
    }

    public function configurationIsCached()
    {
        //
    }

    public function detectEnvironment(\Closure $callback)
    {
        //
    }

    public function environmentFile()
    {
        //
    }

    public function environmentFilePath()
    {
        //
    }

    public function getCachedConfigPath()
    {
        //
    }

    public function getCachedServicesPath()
    {
        //
    }

    public function getCachedPackagesPath()
    {
        //
    }

    public function getCachedRoutesPath()
    {
        //
    }

    public function getLocale()
    {
        //
    }

    public function getNamespace()
    {
        //
    }

    public function getProviders($provider)
    {
        //
    }

    public function hasBeenBootstrapped()
    {
        //
    }

    public function loadDeferredProviders()
    {
        //
    }

    public function loadEnvironmentFrom($file)
    {
        //
    }

    public function routesAreCached()
    {
        //
    }

    public function setLocale($locale)
    {
        //
    }

    public function shouldSkipMiddleware()
    {
        //
    }

    public function terminate()
    {
        //
    }
}
