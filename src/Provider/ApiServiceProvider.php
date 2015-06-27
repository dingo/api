<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Http;
use RuntimeException;
use Dingo\Api\Auth\Auth;
use Dingo\Api\Dispatcher;
use Dingo\Api\Routing\Router;
use Dingo\Api\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;
use Dingo\Api\Routing\ResourceRegistrar;
use Dingo\Api\Exception\Handler as ExceptionHandler;
use Dingo\Api\Transformer\Factory as TransformerFactory;

abstract class ApiServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();

        $this->app->singleton('Illuminate\Contracts\Debug\ExceptionHandler', function ($app) {
            return $app['api.exception'];
        });

        Http\Response::setFormatters($this->prepareConfigValues($this->app['config']['api.formats']));
        Http\Response::setTransformer($this->app['api.transformer']);

        $this->app->booted(function ($app) {
            $app['api.url']->setRouteCollections($app['api.router']->getRoutes());
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->setupClassAliases();

        $this->registerExceptionHandler();
        $this->registerDispatcher();
        $this->registerAuth();
        $this->registerRateLimiting();
        $this->registerRouter();
        $this->registerUrlGenerator();
        $this->registerHttpValidation();
        $this->registerResponseFactory();
        $this->registerMiddleware();
        $this->registerTransformer();

        $this->commands([
            'Dingo\Api\Console\Command\Docs'
        ]);

        if (class_exists('Illuminate\Foundation\Application', false)) {
            $this->commands([
                'Dingo\Api\Console\Command\Cache',
                'Dingo\Api\Console\Command\Routes'
            ]);
        }
    }

    /**
     * Setup the configuration.
     *
     * @return void
     */
    protected function setupConfig()
    {
        if(realpath(base_path().('/config/api.php')))
        {
            $this->mergeConfigFrom(realpath(base_path().'/config/api.php'), 'api');
        }else{
            $this->mergeConfigFrom(realpath(__DIR__.'/../../config/api.php'), 'api');
        }

        $config = $this->app['config']['api'];

        if (! $this->app->runningInConsole() && empty($config['prefix']) && empty($config['domain'])) {
            throw new RuntimeException('Unable to boot ApiServiceProvider, configure an API domain or prefix.');
        }
    }

    /**
     * Setup the class aliases.
     *
     * @return void
     */
    protected function setupClassAliases()
    {
        $this->app->alias('request', 'Dingo\Api\Http\Request');
        $this->app->alias('api.dispatcher', 'Dingo\Api\Dispatcher');
        $this->app->alias('api.http.validator', 'Dingo\Api\Http\Validator');
        $this->app->alias('api.http.response', 'Dingo\Api\Http\Response\Factory');
        $this->app->alias('api.router', 'Dingo\Api\Routing\Router');
        $this->app->alias('api.router.adapter', 'Dingo\Api\Routing\Adapter\AdapterInterface');
        $this->app->alias('api.auth', 'Dingo\Api\Auth\Auth');
        $this->app->alias('api.limiting', 'Dingo\Api\Http\RateLimit\Handler');
        $this->app->alias('api.transformer', 'Dingo\Api\Transformer\Factory');
        $this->app->alias('api.url', 'Dingo\Api\Routing\UrlGenerator');
        $this->app->alias('api.exception', 'Dingo\Api\Exception\Handler');
    }

    /**
     * Register the exception handler.
     *
     * @return void
     */
    protected function registerExceptionHandler()
    {
        $exception = $this->app['Illuminate\Contracts\Debug\ExceptionHandler'];

        $this->app->singleton('api.exception', function ($app) use ($exception) {
            $config = $app['config']['api'];

            return new ExceptionHandler($exception, $config['errorFormat'], $config['debug']);
        });
    }

    /**
     * Register the internal dispatcher.
     *
     * @return void
     */
    public function registerDispatcher()
    {
        $this->app->singleton('api.dispatcher', function ($app) {
            $dispatcher = new Dispatcher($app, $app['files'], $app['api.router'], $app['api.auth']);

            $config = $app['config']['api'];

            $dispatcher->setVendor($config['vendor']);
            $dispatcher->setPrefix($config['prefix']);
            $dispatcher->setDefaultVersion($config['version']);
            $dispatcher->setDefaultDomain($config['domain']);
            $dispatcher->setDefaultFormat($config['defaultFormat']);

            return $dispatcher;
        });
    }

    /**
     * Register the auth.
     *
     * @return void
     */
    protected function registerAuth()
    {
        $this->app->singleton('api.auth', function ($app) {
            $config = $app['config']['api'];

            return new Auth($app['api.router'], $app, $this->prepareConfigValues($config['auth']));
        });
    }

    /**
     * Register the rate limiting.
     *
     * @return void
     */
    protected function registerRateLimiting()
    {
        $this->app->singleton('api.limiting', function ($app) {
            $config = $app['config']['api'];

            return new Http\RateLimit\Handler($app, $app['cache'], $this->prepareConfigValues($config['throttling']));
        });
    }

    /**
     * Register the router.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app->singleton('api.router', function ($app) {
            $config = $app['config']['api'];

            return new Router(
                $app['api.router.adapter'],
                new Http\Parser\Accept($config['vendor'], $config['version'], $config['defaultFormat']),
                $app['api.exception'],
                $app,
                $config['domain'],
                $config['prefix']
            );
        });

        $this->app->singleton('Dingo\Api\Routing\ResourceRegistrar', function ($app) {
            return new ResourceRegistrar($app['api.router']);
        });
    }

    /**
     * Register the URL generator.
     *
     * @return void
     */
    protected function registerUrlGenerator()
    {
        $this->app->singleton('api.url', function ($app) {
            return new UrlGenerator($app['request']);
        });
    }

    /**
     * Register the HTTP validation.
     *
     * @return void
     */
    protected function registerHttpValidation()
    {
        $this->app->singleton('api.http.validator', function ($app) {
            return new Http\Validator($app);
        });

        $this->app->singleton('Dingo\Api\Http\Validation\Domain', function ($app) {
            return new Http\Validation\Domain($app['config']['api.domain']);
        });

        $this->app->singleton('Dingo\Api\Http\Validation\Prefix', function ($app) {
            return new Http\Validation\Prefix($app['config']['api.prefix']);
        });

        $this->app->singleton('Dingo\Api\Http\Validation\Accept', function ($app) {
            $config = $app['config']['api'];

            return new Http\Validation\Accept(
                new Http\Parser\Accept($config['vendor'], $config['version'], $config['defaultFormat'])
            );
        });
    }

    /**
     * Register the response factory.
     *
     * @return void
     */
    protected function registerResponseFactory()
    {
        $this->app->singleton('api.http.response', function ($app) {
            return new Http\Response\Factory($app['api.transformer']);
        });
    }

    /**
     * Register the middleware.
     *
     * @return void
     */
    protected function registerMiddleware()
    {
        $this->app->singleton('Dingo\Api\Http\Middleware\Request', function ($app) {
            return new Http\Middleware\Request($app, $app['api.router'], $app['api.http.validator'], $app['app.middleware']);
        });

        $this->app->singleton('Dingo\Api\Http\Middleware\Auth', function ($app) {
            return new Http\Middleware\Auth($app['api.router'], $app['api.auth']);
        });

        $this->app->singleton('Dingo\Api\Http\Middleware\RateLimit', function ($app) {
            return new Http\Middleware\RateLimit($app['api.router'], $app['api.limiting']);
        });
    }

    /**
     * Register the transformation layer.
     *
     * @return void
     */
    protected function registerTransformer()
    {
        $this->app->singleton('api.transformer', function ($app) {
            return new TransformerFactory($app, $this->prepareConfigValue($app['config']['api.transformer']));
        });
    }

    /**
     * Prepare an array of instantiable configuration instances.
     *
     * @param array $instances
     *
     * @return array
     */
    protected function prepareConfigValues(array $instances)
    {
        foreach ($instances as $key => $value) {
            $instances[$key] = $this->prepareConfigValue($value);
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
    protected function prepareConfigValue($instance)
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
