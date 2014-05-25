<?php namespace Dingo\Api\Auth;

use Cartalyst\Sentry\Sentry;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use Illuminate\Container\Container;
use Dingo\Api\Http\InternalRequest;

class SentryShield extends Shield
{
	/**
	 * @param Sentry    $auth
	 * @param Container $container
	 * @param array     $providers
	 */
	public function __construct(Sentry $auth, Container $container, array $providers)
	{
		$this->auth = $auth;
		$this->container = $container;
		$this->providers = $providers;
	}

	/**
	 * @return \Cartalyst\Sentry\Users\UserInterface|\Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model|null
	 */
	public function getUser()
	{
		if ($this->user) {
			return $this->user;
		} elseif (!$this->userId) {
			return null;
		} elseif (!($user = $this->auth->getUser())) {
			$this->auth->setUser($this->auth->findUserById($this->userId));
		}

		return $this->user = $this->auth->getUser();
	}
}