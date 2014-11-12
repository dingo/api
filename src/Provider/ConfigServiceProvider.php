<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Config;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('dingo/api', 'api', __DIR__.'/../');
    }

    /**
     * Register bindings for the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('api.config', function ($app) {
            $config = $app['config']->get('api::config');

            return new Config(
                $config['version'],
                $config['prefix'],
                $config['domain'],
                $config['vendor'],
                $config['default_format']
            );
        });
    }
}
