<?php namespace Dingo\Api\Facades;

use Closure;
use Dingo\Api\Http\InternalRequest;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Dingo\Api\Routing\Router
 */
class API extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'dingo.api.dispatcher'; }

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
	 * Issue an access token to the API.
	 * 
	 * @param  array  $payload
	 * @return mixed
	 */
	public static function token(array $payload)
	{
		return static::$app['dingo.api.authorization']->token($payload);
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