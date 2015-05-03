<?php

namespace Dingo\Api\Routing\Adapter\Lumen;

use Laravel\Lumen\Application as LumenApplication;

class Application extends LumenApplication
{
    protected $dispatcher;

    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    protected function createDispatcher()
    {
        return $this->dispatcher;
    }
}
