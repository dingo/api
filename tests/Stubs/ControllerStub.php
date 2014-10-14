<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Routing\Controller;
use Dingo\Api\Routing\ControllerTrait;

class ControllerStub extends Controller
{
    use ControllerTrait;

    public function getIndex()
    {
        $_SERVER['ControllerDispatcherTestApi'] = $this->api;
        $_SERVER['ControllerDispatcherTestAuth'] = $this->auth;
        $_SERVER['ControllerDispatcherTestResponse'] = $this->response;

        return 'foo';
    }
}
