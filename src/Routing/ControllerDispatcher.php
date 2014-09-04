<?php

namespace Dingo\Api\Routing;

use Dingo\Api\Dispatcher;
use Dingo\Api\Auth\Authenticator;
use Dingo\Api\Http\ResponseBuilder;
use Illuminate\Routing\ControllerDispatcher as IlluminateControllerDispatcher;

class ControllerDispatcher extends IlluminateControllerDispatcher
{
    /**
     * API dispatcher instance.
     * 
     * @var \Dingo\Api\Dispatcher
     */
    protected $api;

    /**
     * API authenticator instance.
     * 
     * @var \Dingo\Api\Auth\Authenticator
     */
    protected $auth;

    /**
     * API response builder instance.
     * 
     * @var \Dingo\Api\Http\ResponseBuilder
     */
    protected $response;

    /**
     * {@inheritDoc}
     */
    protected function makeController($controller)
    {
        $instance = parent::makeController($controller);

        $this->injectControllerDependencies($instance);

        return $instance;
    }

    /**
     * Inject the controller dependencies into the controller instance.
     * 
     * @param  \Illuminate\Routing\Controller  $instance
     * @return void
     */
    protected function injectControllerDependencies($instance)
    {
        if ($instance instanceof Controller) {
            $instance->setDispatcher($this->getDispatcher());
            $instance->setAuthenticator($this->getAuthenticator());
            $instance->setResponseBuilder($this->getResponseBuilder());
        }
    }

    /**
     * Set the API dispatcher instance.
     * 
     * @param  \Dingo\Api\Dispatcher  $api
     * @return void
     */
    public function setDispatcher(Dispatcher $api)
    {
        $this->api = $api;
    }

    /**
     * Set the API authenticator instance.
     * 
     * @param  \Dingo\Api\Auth\Authenticator  $auth
     * @return void
     */
    public function setAuthenticator(Authenticator $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Set the API response builder instance.
     * 
     * @param  \Dingo\Api\Http\ResponseBuilder  $response
     * @return void
     */
    public function setResponseBuilder(ResponseBuilder $response)
    {
        $this->response = $response;
    }

    /**
     * Get the API dispatcher instance.
     * 
     * @return \Dingo\Api\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->api ?: $this->container['api.dispatcher'];
    }

    /**
     * Get the API authenticator instance.
     * 
     * @return \Dingo\Api\Auth\Authenticator
     */
    public function getAuthenticator()
    {
        return $this->api ?: $this->container['api.auth'];
    }

    /**
     * Get the API response builder instance.
     * 
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function getResponseBuilder()
    {
        return $this->api ?: $this->container['api.response'];
    }
}
