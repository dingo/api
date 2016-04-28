<?php

namespace Dingo\Api\Provider;

use RuntimeException;
use Dingo\Api\Auth\Auth;
use Dingo\Api\Dispatcher;
use Dingo\Api\Http\Request;
use Dingo\Blueprint\Writer;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use Dingo\Api\Console\Command;
use Dingo\Blueprint\Blueprint;
use Dingo\Api\Http\Parser\Accept;
use Dingo\Api\Console\Command\Docs;
use Dingo\Api\Console\Command\Cache;
use Dingo\Api\Console\Command\Routes;
use Dingo\Api\Exception\Handler as ExceptionHandler;
use Dingo\Api\Transformer\Factory as TransformerFactory;
use Dingo\Api\Contract\Debug\ExceptionHandler as ExceptionHandlerContract;

class DingoServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setResponseStaticInstances();

        Request::setAcceptParser($this->app[Accept::class]);

        $this->app->rebinding('api.routes', function ($app, $routes) {
            $app['api.url']->setRouteCollections($routes);
        });
    }

    protected function setResponseStaticInstances()
    {
        Response::setFormatters($this->config('formats'));
        Response::setTransformer($this->app['api.transformer']);
        Response::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();

        $this->registerClassAliases();

        $this->app->register(RoutingServiceProvider::class);

        $this->app->register(HttpServiceProvider::class);

        $this->registerExceptionHandler();

        $this->registerDispatcher();

        $this->registerAuth();

        $this->registerTransformer();

        $this->registerDocsCommand();

        if (class_exists('Illuminate\Foundation\Application', false)) {
            $this->commands([
                Cache::class,
                Routes::class,
            ]);
        }
    }

    /**
     * Register the configuration.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(realpath(__DIR__.'/../../config/api.php'), 'api');

        if (! $this->app->runningInConsole() && empty($this->config('prefix')) && empty($this->config('domain'))) {
            throw new RuntimeException('Unable to boot ApiServiceProvider, configure an API domain or prefix.');
        }
    }

    /**
     * Register the class aliases.
     *
     * @return void
     */
    protected function registerClassAliases()
    {
        $aliases = [
            Request::class       => \Dingo\Api\Contract\Http\Request::class,
            'api.dispatcher'     => \Dingo\Api\Dispatcher::class,
            'api.http.validator' => \Dingo\Api\Http\RequestValidator::class,
            'api.http.response'  => \Dingo\Api\Http\Response\Factory::class,
            'api.router'         => \Dingo\Api\Routing\Router::class,
            'api.router.adapter' => \Dingo\Api\Contract\Routing\Adapter::class,
            'api.auth'           => \Dingo\Api\Auth\Auth::class,
            'api.limiting'       => \Dingo\Api\Http\RateLimit\Handler::class,
            'api.transformer'    => \Dingo\Api\Transformer\Factory::class,
            'api.url'            => \Dingo\Api\Routing\UrlGenerator::class,
            'api.exception'      => [\Dingo\Api\Exception\Handler::class, ExceptionHandlerContract::class],
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
            return new ExceptionHandler($app[ExceptionHandlerContract::class], $this->config('errorFormat'), $this->config('debug'));
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
            $dispatcher = new Dispatcher($app, $app['files'], $app[Router::class], $app[Auth::class]);

            $dispatcher->setSubtype($this->config('subtype'));
            $dispatcher->setStandardsTree($this->config('standardsTree'));
            $dispatcher->setPrefix($this->config('prefix'));
            $dispatcher->setDefaultVersion($this->config('version'));
            $dispatcher->setDefaultDomain($this->config('domain'));
            $dispatcher->setDefaultFormat($this->config('defaultFormat'));

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
            return new Auth($app[Router::class], $app, $this->config('auth'));
        });
    }

    /**
     * Register the transformer factory.
     *
     * @return void
     */
    protected function registerTransformer()
    {
        $this->app->singleton('api.transformer', function ($app) {
            return new TransformerFactory($app, $this->config('transformer'));
        });
    }

    /**
     * Register the documentation command.
     *
     * @return void
     */
    protected function registerDocsCommand()
    {
        $this->app->singleton(Docs::class, function ($app) {
            return new Command\Docs(
                $app[Router::class],
                $app[Blueprint::class],
                $app[Writer::class],
                $this->config('name'),
                $this->config('version')
            );
        });

        $this->commands([Docs::class]);
    }
}
