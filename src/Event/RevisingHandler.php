<?php

namespace Dingo\Api\Event;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Dingo\Api\Routing\ControllerReviser;

class RevisingHandler
{
    /**
     * API router instance.
     *
     * @var \Dingo\Api\Routing\Router
     */
    protected $router;

    /**
     * API controller reviser instance.
     *
     * @var \Dingo\Api\Routing\ControllerReviser
     */
    protected $reviser;

    /**
     * Create a new revising handler instance.
     *
     * @param \Dingo\Api\Routing\Router            $router
     * @param \Dingo\Api\Routing\ControllerReviser $reviser
     *
     * @return void
     */
    public function __construct(Router $router, ControllerReviser $reviser)
    {
        $this->router = $router;
        $this->reviser = $reviser;
    }

    /**
     * Handle the revising of a controller for API requests.
     *
     * @param \Illuminate\Routing\Route $route
     * @param \Illuminate\Http\Request  $request
     *
     * @return void
     */
    public function handle(Route $route, Request $request)
    {
        if ($this->router->isApiRequest($request)) {
            $this->reviser->revise($route);
        }
    }
}
