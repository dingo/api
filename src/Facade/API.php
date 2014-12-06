<?php

namespace Dingo\Api\Facade;

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
        return 'api.dispatcher';
    }

    /**
     * Bind an exception handler.
     *
     * @param \Closure $callback
     *
     * @return void
     */
    public static function error(Closure $callback)
    {
        return static::$app['api.exception']->register($callback);
    }

    /**
     * Register a class transformer.
     *
     * @param string          $class
     * @param string|\Closure $transformer
     *
     * @return \Dingo\Api\Transformer\Binding
     */
    public static function transform($class, $transformer)
    {
        return static::$app['api.transformer']->register($class, $transformer);
    }

    /**
     * Get the authentication provider.
     *
     * @return \Dingo\Api\Auth\Provider
     */
    public static function auth()
    {
        return static::$app['api.auth'];
    }

    /**
     * Get the authenticated API user.
     *
     * @return \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
     */
    public static function user()
    {
        return static::$app['api.auth']->getUser();
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

    /**
     * Get the response factory to begin building a response.
     *
     * @return \Dingo\Api\Http\ResponseFactory
     */
    public static function response()
    {
        return static::$app['api.response'];
    }
}
