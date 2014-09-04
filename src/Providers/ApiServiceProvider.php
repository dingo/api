<?php

namespace Dingo\Api\Providers;

use RuntimeException;
use Dingo\Api\Dispatcher;
use Dingo\Api\Http\Response;
use Dingo\Api\Auth\Authenticator;
use Dingo\Api\Http\ResponseBuilder;
use League\Fractal\Manager as Fractal;
use Illuminate\Support\ServiceProvider;
use Dingo\Api\Console\ApiRoutesCommand;
use Dingo\Api\Http\RateLimit\RateLimiter;
use Dingo\Api\Transformer\FractalTransformer;
use Illuminate\Support\Facades\Response as ResponseFacade;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('dingo/api', 'api', __DIR__.'/../');

        $this->app->bind('Dingo\Api\Dispatcher', function ($app) {
            return $app['api.dispatcher'];
        });

        $this->app->bind('Dingo\Api\Auth\Shield', function ($app) {
            return $app['api.auth'];
        });

        $this->app->bind('Dingo\Api\Http\ResponseBuilder', function ($app) {
            return $app['api.response'];
        });

        ResponseFacade::macro('api', function () {
            return $this->app['api.response'];
        });

        Response::setTransformer($this->app['api.transformer']);

        $this->prepareResponseFormats();
    }

    /**
     * Boot the response formats.
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function prepareResponseFormats()
    {
        $formats = $this->prepareConfigInstances($this->app['config']['api::formats']);

        if (empty($formats)) {
            throw new RuntimeException('No registered response formats.');
        }

        Response::setFormatters($formats);
    }

    /**
     * Register bindings for the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerProviders();
        $this->registerDispatcher();
        $this->registerTransformer();
        $this->registerAuthenticator();
        $this->registerRateLimiter();
        $this->registerResponseBuilder();
        $this->registerCommands();
    }

    /**
     * Register the remaining service providers.
     * 
     * @return void
     */
    protected function registerProviders()
    {
        $this->app->register('Dingo\Api\Providers\RoutingServiceProvider');
        $this->app->register('Dingo\Api\Providers\FilterServiceProvider');
        $this->app->register('Dingo\Api\Providers\EventServiceProvider');
    }

    /**
     * Register the API dispatcher.
     *
     * @return void
     */
    protected function registerDispatcher()
    {
        $this->app->bindShared('api.dispatcher', function ($app) {
            return new Dispatcher($app['request'], $app['url'], $app['router'], $app['api.auth']);
        });
    }

    /**
     * Register the API transformer.
     *
     * @return void
     */
    protected function registerTransformer()
    {
        $this->app->bindShared('api.transformer', function ($app) {
            $transformer = $this->prepareConfigInstance($app['config']['api::transformer']);

            $transformer->setContainer($app);

            return $transformer;
        });
    }

    /**
     * Register the API authenticator.
     *
     * @return void
     */
    protected function registerAuthenticator()
    {
        $this->app->bindShared('api.auth', function ($app) {
            $providers = $this->prepareConfigInstances($app['config']['api::auth']);

            return new Authenticator($app['router'], $app, $providers);
        });
    }

    /**
     * Register the API rate limiter.
     * 
     * @return void
     */  
    protected function registerRateLimiter()
    {
        $this->app->bindShared('api.limiter', function ($app) {
            $throttles = $this->prepareConfigInstances($app['config']['api::throttling']);

            return new RateLimiter($app['cache'], $app, $throttles);
        });
    }

    /**
     * Register the API response builder.
     * 
     * @return void
     */
    protected function registerResponseBuilder()
    {
        $this->app->bindShared('api.response', function ($app) {
            $transformer = $app['api.transformer'];

            if (! $transformer instanceof FractalTransformer) {
                $transformer = new FractalTransformer(new Fractal);
            }

            return new ResponseBuilder($transformer);
        });
    }

    /**
     * Register the commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->app->bindShared('api.command.routes', function ($app) {
            return new ApiRoutesCommand($app['router']);
        });

        $this->commands('api.command.routes');
    }

    /**
     * Prepare an array of instantiable configuration instances.
     * 
     * @param  array  $instances
     * @return array
     */
    protected function prepareConfigInstances(array $instances)
    {
        foreach ($instances as $key => $value) {
            $instances[$key] = $this->prepareConfigInstance($value);
        }

        return $instances;
    }

    /**
     * Prepare an instantiable configuration instance.
     * 
     * @param  mixed  $instance
     * @return object
     */
    protected function prepareConfigInstance($instance)
    {
        if (is_callable($instance)) {
            return call_user_func($instance, $this->app);
        } elseif (is_string($instance)) {
            return $this->app->make($instance);
        } else {
            return $instance;
        }
    }
}
