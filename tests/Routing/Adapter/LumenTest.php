<?php

namespace Dingo\Api\Tests\Routing\Adapter;

use Laravel\Lumen\Application;
use Dingo\Api\Routing\Adapter\Lumen;
use FastRoute\RouteParser\Std as StdRouteParser;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;

class LumenTest extends BaseAdapterTest
{
    public function getAdapterInstance()
    {
        $app = new Application;

        $app->routeMiddleware([
            'api.auth' => get_class($this->container['api.auth']),
            'api.limiting' => get_class($this->container['api.limiting']),
        ]);

        return new Lumen($app, new StdRouteParser, new GcbDataGenerator, GcbDispatcher::class);
    }

    public function testRoutesWithDomains()
    {
        $this->markTestSkipped('Lumen does not support sub-domain routing.');
    }
}
