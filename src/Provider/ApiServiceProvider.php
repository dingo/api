<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Dispatcher;
use Dingo\Api\Properties;
use Dingo\Api\Http\Response;
use Dingo\Api\Exception\Handler;
use Dingo\Api\Auth\Authenticator;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\ResponseFactory;
use Illuminate\Support\ServiceProvider;
use Dingo\Api\Console\ApiRoutesCommand;
use Dingo\Api\Http\RateLimit\RateLimiter;
use Dingo\Api\Transformer\TransformerFactory;

use Illuminate\Routing\Matching\UriValidator;
use Illuminate\Routing\Matching\HostValidator;
use Dingo\Api\Routing\Matching\AcceptValidator;
use Illuminate\Routing\Matching\SchemeValidator;
use Illuminate\Routing\Matching\MethodValidator;

use Dingo\Api\Routing\Route;

use Dingo\Api\Http\Matcher;
use Dingo\Api\Http\Middleware;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/api.php' => config_path('api.php')
        ]);

        $this->app['router']->middleware('api', 'Dingo\Api\Http\Middleware\RouteMiddleware');

        Response::setFormatters([
            'json' => new \Dingo\Api\Http\ResponseFormat\JsonResponseFormat
        ]);

        Route::$validators = [
            new MethodValidator, new SchemeValidator,
            new HostValidator, new UriValidator,
            new AcceptValidator
        ];

        $this->setupContainerBindings();
    }

    protected function replaceRouter()
    {
        $routes = $this->app['router']->getRoutes();

        $this->app->bindShared('router', function ($app) use ($routes) {
            $router = new Router($app['events'], $app);

            $router->addExistingRoutes($routes);

            return $router;
        });
    }

    /**
     * Prepare the container bindings.
     *
     * @return void
     */
    protected function setupContainerBindings()
    {
        $this->app->bind('Dingo\Api\Http\Middleware\RequestMiddleware', function ($app) {
            return $app['api.middlewares.request'];
        });
    }

    /**
     * Register bindings for the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->replaceRouter();

        $this->mergeConfigFrom(__DIR__.'/../../config/api.php', 'api');

        $this->registerProperties();
        $this->registerMiddlewares();
    }

    protected function registerProperties()
    {
        $this->app->bindShared('api.properties', function ($app) {
            $properties = $app['config']->get('api');

            return new Properties(
                $properties['version'],
                $properties['prefix'],
                $properties['domain'],
                $properties['vendor'],
                $properties['default_format']
            );
        });
    }

    protected function registerMiddlewares()
    {
        $this->app->bindShared('api.middlewares.request', function ($app) {
            return new Middleware\RequestMiddleware(new Matcher($app['api.properties']));
        });
    }

    /**
     * Register the remaining service providers.
     *
     * @return void
     */
    protected function registerProviders()
    {
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
