<?php namespace Dingo\Api\Auth;

use Illuminate\Support\Manager;

class ProviderManager extends Manager {

	/**
	 * Create Dingo's OAuth 2.0 authentication driver.
	 * 
	 * @return \Dingo\Api\Auth\DingoOAuth2Provider
	 */
	public function createDingoOAuth2Driver()
	{
		return new DingoOAuth2Provider($this->app['dingo.oauth.resource']);
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

	/**
	 * Create a new driver instance.
	 *
	 * @param  string  $driver
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	protected function createDriver($driver)
	{
		return parent::createDriver(str_replace('.', '', $driver));
	}

}
