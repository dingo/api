<?php

namespace Dingo\Api\Routing;

use Illuminate\Routing\ControllerDispatcher as IlluminateControllerDispatcher;

class ControllerDispatcher extends IlluminateControllerDispatcher
{
	public function __construct($filterer, $api, $auth, $response, $container = null)
	{
		$this->filterer = $filterer;
		$this->api = $api;
		$this->auth = $auth;
		$this->response = $response;
		$this->container = $container;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function makeController($controller)
	{
		$instance = parent::makeController($controller);

		$this->setControllerDependencies($instance);

		return $instance;
	}

	protected function setControllerDependencies($instance)
	{
		$instance->setDispatcher($this->container['api.dispatcher']);
		$instance->setAuthenticator($this->container['api.auth']);
		$instance->setResponseBuilder($this->container['api.response']);
	}
}
