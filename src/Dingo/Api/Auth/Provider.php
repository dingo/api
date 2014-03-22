<?php namespace Dingo\Api\Auth;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Auth\GenericUser;

class Provider {

	/**
	 * The authentication provider.
	 * 
	 * @var \Dingo\Api\Auth\ProviderInterface
	 */
	protected $provider;

	/**
	 * Create a new Dingo\Api\Auth\Provider instance.
	 * 
	 * @param  Dingo\Api\Auth\ProviderInterface  $provider
	 * @return void
	 */
	public function __construct(ProviderInterface $provider)
	{
		$this->provider = $provider;
	}

	/**
	 * Authenticate a given request.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Auth\GenericUser
	 */
	public function authenticate(Request $request)
	{
		if ( ! $this->validateAuthorizationHeader($request))
		{
			throw new Exception;
		}

		return $this->provider->authenticate();
	}

	/**
	 * Validate the requests authorization header for the provider.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return bool
	 */
	protected function validateAuthorizationHeader(Request $request)
	{
		return starts_with(strtolower($request->headers->get('authorization')), $this->provider->getAuthorizationMethod());
	}

}