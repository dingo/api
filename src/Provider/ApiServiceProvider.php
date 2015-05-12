<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Http;
use Dingo\Api\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Dingo\Api\Transformer\TransformerFactory;
use Dingo\Api\Exception\Handler as ExceptionHandler;

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
            __DIR__.'/../../config/api.php' => config_path('api.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/api.php', 'api');

        $this->registerExceptionHandler();
        $this->registerRouter();
        $this->registerHttpValidation();
        $this->registerMiddleware();
        $this->registerTransformer();
        $this->setupClassAliases();

        Http\Response::setFormatters($this->prepareConfigValues($this->app['config']['api.formats']));
        Http\Response::setTransformer($this->app['api.transformer']);
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

            return new ExceptionHandler($config['error_format'], $config['debug']);
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
                new Http\Parser\AcceptParser($config['vendor'], $config['version'], $config['default_format']),
                $app['api.exception'],
                $app
            );
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

        $this->app->singleton('Dingo\Api\Http\Validation\DomainValidator', function ($app) {
            return new Http\Validation\DomainValidator($app['config']['api.domain']);
        });

        $this->app->singleton('Dingo\Api\Http\Validation\PrefixValidator', function ($app) {
            return new Http\Validation\PrefixValidator($app['config']['api.prefix']);
        });

        $this->app->singleton('Dingo\Api\Http\Validation\AcceptValidator', function ($app) {
            $config = $app['config']['api'];

            return new Http\Validation\AcceptValidator(
                new Http\Parser\AcceptParser($config['vendor'], $config['version'], $config['default_format'])
            );
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
     * Setup the class aliases.
     *
     * @return void
     */
    protected function setupClassAliases()
    {
        $this->app->alias('api.http.validator', 'Dingo\Api\Http\Validator');
        $this->app->alias('api.router', 'Dingo\Api\Routing\Router');
        $this->app->alias('api.router.adapter', 'Dingo\Api\Routing\Adapter\AdapterInterface');
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
