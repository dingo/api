<?php namespace Dingo\Api\Auth;

use RuntimeException;
use Illuminate\Support\Manager;

class ProviderManager extends Manager {

	/**
	 * Create OAuth 2.0 authentication driver.
	 * 
	 * @return \Dingo\Api\Auth\LeagueOAuth2Provider
	 */
	public function createOAuth2Driver()
	{
		if ($this->app->bound('oauth2.resource-server'))
		{
			$httpHeadersOnly = $this->app['config']->get('lucadegasperi/oauth2-server-laravel::oauth2.http_headers_only');
		
			return new LeagueOAuth2Provider($this->app['oauth2.resource-server'], $httpHeadersOnly);
		}
		elseif ($this->app->bound('dingo.oauth.resource'))
		{
			return new DingoOAuth2Provider($this->app['dingo.oauth.resource']);
		}

		throw new RuntimeException('Unable to resolve either OAuth 2.0 resource server binding.');
	}

	/**
	 * Create Basic authentication provider.
	 * 
	 * @return \Dingo\Api\Auth\BasicProvider
	 */
	public function createBasicDriver()
	{
		return new BasicProvider($this->app['auth']);
	}

}
