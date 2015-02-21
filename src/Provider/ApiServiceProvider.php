<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Dispatcher;
use Dingo\Api\Http\Response;
use Dingo\Api\Exception\Handler;
use Dingo\Api\Auth\Authenticator;
use Dingo\Api\Http\ResponseFactory;
use Illuminate\Support\ServiceProvider;
use Dingo\Api\Console\ApiRoutesCommand;
use Dingo\Api\Http\RateLimit\RateLimiter;
use Dingo\Api\Transformer\TransformerFactory;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupContainerBindings();

        Response::setFormatters($this->prepareConfigInstances($this->app['config']['api::formats']));
        Response::setTransformer($this->app['api.transformer']);
    }

    /**
     * Prepare the container bindings.
     *
     * @return void
     */
    protected function setupContainerBindings()
    {
        $this->app->bind('Dingo\Api\Dispatcher', function ($app) {
            return $app['api.dispatcher'];
        });

        $this->app->bind('Dingo\Api\Auth\Authenticator', function ($app) {
            return $app['api.auth'];
        });

        $this->app->bind('Dingo\Api\Http\ResponseFactory', function ($app) {
            return $app['api.response'];
        });
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
        $this->registerResponseFactory();
        $this->registerExceptionHandler();
        $this->registerCommands();
    }

    /**
     * Register the remaining service providers.
     *
     * @return void
     */
    protected function registerProviders()
    {
        $this->app->register('Dingo\Api\Provider\PropertiesServiceProvider');
        $this->app->register('Dingo\Api\Provider\RoutingServiceProvider');
        $this->app->register('Dingo\Api\Provider\FilterServiceProvider');
        $this->app->register('Dingo\Api\Provider\EventServiceProvider');
    }

    /**
     * Register the API dispatcher.
     *
     * @return void
     */
    protected function registerDispatcher()
    {
        $this->app->bindShared('api.dispatcher', function ($app) {
            return new Dispatcher($app, $app['files'], $app['url'], $app['router'], $app['api.auth'], $app['api.properties']);
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

            return new TransformerFactory($app, $transformer);
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

            $limiter = new RateLimiter($app, $app['cache'], $throttles);

            $limiter->setRateLimiter(function ($container, $request) {
                return $request->getClientIp();
            });

            return $limiter;
        });
    }

    /**
     * Register the API response factory.
     *
     * @return void
     */
    protected function registerResponseFactory()
    {
        $this->app->bindShared('api.response', function ($app) {
            return new ResponseFactory($app['api.transformer']);
        });
    }

    /**
     * Register the API exception handler.
     *
     * @return void
     */
    protected function registerExceptionHandler()
    {
        $this->app->bindShared('api.exception', function ($app) {
            return new Handler;
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
     * @param array $instances
     *
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
     * @param mixed $instance
     *
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
