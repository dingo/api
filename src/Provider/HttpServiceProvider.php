<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Http\Middleware;
use Dingo\Api\Http\Validation;
use Dingo\Api\Http\RequestValidator;
use Dingo\Api\Http\Parser\Accept as AcceptParser;
use Dingo\Api\Http\Response\Factory as ResponseFactory;
use Dingo\Api\Http\RateLimit\Handler as RateLimitHandler;

class HttpServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRateLimiting();

        $this->registerHttpValidation();

        $this->registerHttpParsers();

        $this->registerResponseFactory();

        $this->registerMiddleware();
    }

    /**
     * Register the rate limiting.
     *
     * @return void
     */
    protected function registerRateLimiting()
    {
        $this->app->singleton('api.limiting', function ($app) {
            return new RateLimitHandler($app, $app['cache'], $this->config('throttling'));
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
            return new RequestValidator($app);
        });

        $this->app->singleton('Dingo\Api\Http\Validation\Domain', function ($app) {
            return new Validation\Domain($this->config('domain'));
        });

        $this->app->singleton('Dingo\Api\Http\Validation\Prefix', function ($app) {
            return new Validation\Prefix($this->config('prefix'));
        });

        $this->app->singleton('Dingo\Api\Http\Validation\Accept', function ($app) {
            return new Validation\Accept(
                $this->app['Dingo\Api\Http\Parser\Accept'],
                $this->config('strict')
            );
        });
    }

    /**
     * Register the HTTP parsers.
     *
     * @return void
     */
    protected function registerHttpParsers()
    {
        $this->app->singleton('Dingo\Api\Http\Parser\Accept', function ($app) {
            return new AcceptParser(
                $this->config('standardsTree'),
                $this->config('subtype'),
                $this->config('version'),
                $this->config('defaultFormat')
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
            return new ResponseFactory($app['Dingo\Api\Transformer\Factory']);
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
            $middleware = new Middleware\Request(
                $app,
                $app['Dingo\Api\Contract\Debug\ExceptionHandler'],
                $app['Dingo\Api\Routing\Router'],
                $app['Dingo\Api\Http\RequestValidator'],
                $app['events']
            );

            $middleware->setMiddlewares($this->config('middleware', false));

            return $middleware;
        });

        $this->app->singleton('Dingo\Api\Http\Middleware\Auth', function ($app) {
            return new Middleware\Auth($app['Dingo\Api\Routing\Router'], $app['Dingo\Api\Auth\Auth']);
        });

        $this->app->singleton('Dingo\Api\Http\Middleware\RateLimit', function ($app) {
            return new Middleware\RateLimit($app['Dingo\Api\Routing\Router'], $app['Dingo\Api\Http\RateLimit\Handler']);
        });

        $this->app->singleton('Dingo\Api\Http\Middleware\PrepareController', function ($app) {
            return new Middleware\PrepareController($app['Dingo\Api\Routing\Router']);
        });
    }
}
