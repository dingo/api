<?php

namespace Dingo\Api\Routing;

use Dingo\Api\Dispatcher;
use Dingo\Api\Auth\Authenticator;
use Dingo\Api\Http\ResponseFactory;
use Illuminate\Routing\ControllerDispatcher as IlluminateControllerDispatcher;

class ControllerDispatcher extends IlluminateControllerDispatcher
{
    /**
     * {@inheritDoc}
     */
    protected function makeController($controller)
    {
        $instance = parent::makeController($controller);

        if (in_array('Dingo\Api\Routing\ControllerTrait', class_uses($instance))) {
            $this->injectControllerDependencies($instance);
        }

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
        $instance->setDispatcher($this->container['api.dispatcher']);
        $instance->setAuthenticator($this->container['api.auth']);
        $instance->setResponseFactory($this->container['api.response']);
    }
}
