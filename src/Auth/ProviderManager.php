<?php namespace Dingo\Api\Auth;

use Illuminate\Support\Manager;

class ProviderManager extends Manager {

	/**
	 * Create OAuth 2.0 provider.
	 * 
	 * @return \Dingo\Api\Auth\BasicProvider
	 */
	public function createOAuth2Driver()
	{
		return new OAuth2Provider($this->app['dingo.oauth.resource']);
	}

	/**
	 * Create basic provider.
	 * 
	 * @return \Dingo\Api\Auth\BasicProvider
	 */
	public function createBasicDriver()
	{
		return new BasicProvider($this->app['auth']);
	}

}
