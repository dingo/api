<?php namespace Dingo\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

abstract class Provider {

	/**
	 * Authenticate the request and return the authenticated users ID.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Illuminate\Routing\Route  $route
	 * @return int
	 */
	abstract public function authenticate(Request $request, Route $route);

}
