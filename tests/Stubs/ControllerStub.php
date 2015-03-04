<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\ControllerTrait;
use Illuminate\Routing\Controller;

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
