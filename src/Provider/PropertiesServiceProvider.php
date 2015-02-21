<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Properties;
use Illuminate\Support\ServiceProvider;

class PropertiesServiceProvider extends ServiceProvider
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
        $this->app->bindShared('api.properties', function ($app) {
            $properties = $app['config']->get('api::config');

            return new Properties(
                $properties['version'],
                $properties['prefix'],
                $properties['domain'],
                $properties['vendor'],
                $properties['default_format']
            );
        });
    }
}
