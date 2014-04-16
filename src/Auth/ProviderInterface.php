<?php namespace Dingo\Api\Auth;

use Illuminate\Http\Request;

interface ProviderInterface {

	/**
	 * Authenticate the request.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return int
	 */
	public function authenticate(Request $request);

}