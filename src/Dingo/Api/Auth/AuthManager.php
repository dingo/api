<?php namespace Dingo\Api\Auth;

use Illuminate\Support\Manager;

class AuthManager extends Manager {

	/**
	 * Create the basic auth provider.
	 * 
	 * @return \Dingo\Api\Auth\Provider
	 */
	public function createBasicDriver()
	{
		return new Provider(new BasicProvider($this->app['auth']));
	}

	/**
	 * Create the OAuth2 auth provider.
	 * 
	 * @return \Dingo\Api\Auth\Provider
	 */
	public function createOAuthDriver()
	{
		return new Provider(new OAuthProvider($this->app['auth'], $this->app['dingo.oauth.resource']));
	}

}