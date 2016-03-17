<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Http;
use RuntimeException;
use Dingo\Api\Auth\Auth;
use Dingo\Api\Dispatcher;
use Dingo\Api\Routing\Router;
use Dingo\Api\Console\Command;
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
        $config = $this->app['config']['api'];

        Http\Response::setFormatters($this->prepareConfigValues($config['formats']));
        Http\Response::setTransformer($this->app['api.transformer']);
        Http\Response::setEventDispatcher($this->app['events']);

        Http\Request::setAcceptParser(
            new Http\Parser\Accept($config['standardsTree'], $config['subtype'], $config['version'], $config['defaultFormat'])
        );

        $this->app->rebinding('api.routes', function ($app, $routes) {
            $app['api.url']->setRouteCollections($routes);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->setupConfig();
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
        $this->registerDocsCommand();

        $this->commands([
            'Dingo\Api\Console\Command\Docs',
        ]);

        if (class_exists('Illuminate\Foundation\Application', false)) {
            $this->commands([
                'Dingo\Api\Console\Command\Cache',
                'Dingo\Api\Console\Command\Routes',
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
        $this->mergeConfigFrom(realpath(__DIR__.'/../../config/api.php'), 'api');

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
        $this->app->alias('Dingo\Api\Http\Request', 'Dingo\Api\Contract\Http\Request');

        $aliases = [
            'api.dispatcher'     => 'Dingo\Api\Dispatcher',
            'api.http.validator' => 'Dingo\Api\Http\RequestValidator',
            'api.http.response'  => 'Dingo\Api\Http\Response\Factory',
            'api.router'         => 'Dingo\Api\Routing\Router',
            'api.router.adapter' => 'Dingo\Api\Contract\Routing\Adapter',
            'api.auth'           => 'Dingo\Api\Auth\Auth',
            'api.limiting'       => 'Dingo\Api\Http\RateLimit\Handler',
            'api.transformer'    => 'Dingo\Api\Transformer\Factory',
            'api.url'            => 'Dingo\Api\Routing\UrlGenerator',
            'api.exception'      => ['Dingo\Api\Exception\Handler', 'Dingo\Api\Contract\Debug\ExceptionHandler'],
        ];

        foreach ($aliases as $key => $aliases) {
            foreach ((array) $aliases as $alias) {
                $this->app->alias($key, $alias);
            }
        }
    }

    /**
     * Register the exception handler.
     *
     * @return void
     */
    protected function registerExceptionHandler()
    {
        $this->app->singleton('api.exception', function ($app) {
            $config = $app['config']['api'];

            return new ExceptionHandler($app['Illuminate\Contracts\Debug\ExceptionHandler'], $config['errorFormat'], $config['debug']);
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

            $dispatcher->setSubtype($config['subtype']);
            $dispatcher->setStandardsTree($config['standardsTree']);
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

            $router = new Router(
                $app['api.router.adapter'],
                $app['Dingo\Api\Contract\Debug\ExceptionHandler'],
                $app,
                $config['domain'],
                $config['prefix']
            );

            $router->setConditionalRequest($config['conditionalRequest']);

            return $router;
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
            $url = new UrlGenerator($app['request']);

            $url->setRouteCollections($app['api.router']->getRoutes());

            return $url;
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
            return new Http\RequestValidator($app);
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
                new Http\Parser\Accept($config['standardsTree'], $config['subtype'], $config['version'], $config['defaultFormat']),
                $config['strict']
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
            $middleware = array_merge($app['app.middleware'], $app['config']['api.middleware']);

            return new Http\Middleware\Request($app, $app['api.exception'], $app['api.router'], $app['api.http.validator'], $middleware);
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
     * Register the documentation command.
     *
     * @return void
     */
    protected function registerDocsCommand()
    {
        $this->app->singleton('Dingo\Api\Console\Command\Docs', function ($app) {
            $config = $app['config']['api'];

            return new Command\Docs(
                $app['api.router'],
                $app['Dingo\Blueprint\Blueprint'],
                $app['Dingo\Blueprint\Writer'],
                $config['name'],
                $config['version']
            );
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
        if (is_string($instance)) {
            return $this->app->make($instance);
        }

        return $instance;
    }
}
