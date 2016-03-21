<?php

namespace Dingo\Api\Tests\Routing\Adapter;

use Illuminate\Routing\Router;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Dingo\Api\Routing\Adapter\Laravel;
use Illuminate\Routing\RouteCollection;

class LaravelTest extends BaseAdapterTest
{
    public function getAdapterInstance()
    {
        return new Laravel($this->container, new Router(new Dispatcher, $this->container), new RouteCollection);
    }

    public function getContainerInstance()
    {
        return new Container;
    }
}
