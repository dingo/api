<?php namespace Dingo\Api\Auth;

interface ProviderInterface {

	/**
	 * Authenticate request.
	 * 
	 * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Auth\GenericUser
	 */
	public function authenticate();

	/**
	 * Get the providers authorization method.
	 * 
	 * @return string
	 */
	public function getAuthorizationMethod();

}