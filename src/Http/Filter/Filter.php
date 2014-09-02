<?php

namespace Dingo\Api\Http\Filter;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Http\InternalRequest;

abstract class Filter
{
	/**
	 * Indicates if a request is internal.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return bool
	 */
	protected function requestIsInternal(Request $request)
	{
		return $request instanceof InternalRequest;
	}

	/**
	 * Indicates if the request is regular request.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return bool
	 */
	protected function requestIsRegular(Request $request)
	{
		return ! $this->router->requestTargettingApi($request);
	}

	/**
	 * Indicates if the request is an API request.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return bool
	 */
	protected function requestIsApi(Request $request)
	{
		return $this->router->requestTargettingApi($request);
	}

	/**
	 * Indicates if a route is not protected.
	 * 
	 * @param  \Dingo\Api\Routing\Route  $route
	 * @return bool
	 */
	protected function routeNotProtected(Route $route)
	{
		return ! $route->isProtected();
	}

	/**
	 * Indicates if a user is logged in.
	 * 
	 * @return bool
	 */
	protected function userIsLogged()
	{
		return $this->auth->check();
	}
}
