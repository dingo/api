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

        if ($this->controllerHasTrait($instance)) {
            $this->injectControllerDependencies($instance);
        }

        return $instance;
    }

    /**
     * Determine if the controller instance has the trait.
     *
     * @param  object  $instance
     * @return bool
     */
    protected function controllerHasTrait($instance)
    {
        $traits = class_uses($instance);

        foreach (class_parents($instance) as $parent) {
            $traits = array_merge($traits, class_uses($parent));
        }

        return in_array('Dingo\Api\Routing\ControllerTrait', $traits);
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
