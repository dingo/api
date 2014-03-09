<?php namespace Dingo\Api\Facades;

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
	protected static function getFacadeAccessor() { return 'dingo.api'; }

}