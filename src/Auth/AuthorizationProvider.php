<?php namespace Dingo\Api\Auth;

use Exception;
use Illuminate\Http\Request;

abstract class AuthorizationProvider implements ProviderInterface {

	/**
	 * Array of provider speicifc options.
	 * 
	 * @var array
	 */
	protected $options = [];

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
	 * Get the providers authorization method.
	 * 
	 * @return string
	 */
	abstract public function getAuthorizationMethod();

}