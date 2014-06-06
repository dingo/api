<?php

namespace Dingo\Api\Facades;

use Closure;
use Dingo\Api\Http\InternalRequest;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Dingo\Api\Routing\Router
 */
class API extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'dingo.api.dispatcher';
    }

    /**
     * Bind an exception handler.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function error(Closure $callback)
    {
        return static::$app['dingo.api.exception']->register($callback);
    }

    /**
     * Transform a class into a Fractal transformer.
     *
     * @param  string  $class
     * @param  string|\Closure  $transformer
     * @return \Dingo\Api\Transformer
     */
    public static function transform($class, $transformer)
    {
        return static::$app['dingo.api.transformer']->registerBinding($class, $transformer);
    }

    /**
     * Get the authentication provider.
     *
     * @return \Dingo\Api\Auth\Provider
     */
    public static function auth()
    {
        return static::$app['dingo.api.auth'];
    }

    /**
     * Get the authenticated API user.
     *
     * @return \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
     */
    public static function user()
    {
        return static::$app['dingo.api.auth']->getUser();
    }

    /**
     * Determine if a request is internal.
     *
     * @return bool
     */
    public static function internal()
    {
        return static::$app['router']->getCurrentRequest() instanceof InternalRequest;
    }
}
