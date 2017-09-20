<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

class ApplicationStub extends Container implements Application
{
    public function version()
    {
        return 'v1';
    }

    public function basePath()
    {
        //
    }

    public function environment()
    {
        return 'testing';
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

    public function getCachedCompilePath()
    {
        //
    }

    public function getCachedServicesPath()
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

    public function getCachedPackagesPath()
    {
        //
    }

    public function runningInConsole()
    {
        //
    }
}
