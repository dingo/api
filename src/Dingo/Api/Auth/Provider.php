<?php namespace Dingo\Api\Auth;

use Exception;
use Illuminate\Http\Request;

abstract class Provider {

	/**
	 * Validate the requests authorization header for the provider.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return bool
	 */
	public function validateAuthorizationHeader(Request $request)
	{
		if ( ! starts_with(strtolower($request->headers->get('authorization')), $this->getAuthorizationMethod()))
		{
			throw new Exception;
		}
	}

	/**
	 * Authenticate request.
	 * 
	 * @param  array  $scopes
	 * @return int
	 */
	abstract public function authenticate(array $scopes);

	/**
	 * Get the providers authorization method.
	 * 
	 * @return string
	 */
	abstract public function getAuthorizationMethod();

}