<?php

namespace Dingo\Api\Routing;

use Illuminate\Routing\ResourceRegistrar as IlluminateResourceRegistrar;

class ResourceRegistrar extends IlluminateResourceRegistrar
{
    /**
     * Create a new resource registrar instance.
     *
     * @param \Dingo\Api\Routing\Router $router
     *
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }
}
