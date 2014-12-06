<?php

namespace Dingo\Api\Routing;

use Dingo\Api\Dispatcher;
use BadMethodCallException;
use Illuminate\Routing\ControllerDispatcher as IlluminateControllerDispatcher;

class ControllerDispatcher extends IlluminateControllerDispatcher
{
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
     * @param \Illuminate\Routing\Controller $instance
     *
     * @return void
     */
    protected function injectControllerDependencies($instance)
    {
        try {
            $instance->setDispatcher($this->container['api.dispatcher']);
            $instance->setAuthenticator($this->container['api.auth']);
            $instance->setResponseFactory($this->container['api.response']);
        } catch (BadMethodCallException $exception) {
            // This controller does not utilize the trait.
        }
    }
}
