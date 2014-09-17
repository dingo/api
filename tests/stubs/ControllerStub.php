<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\Controller;

class ControllerStub extends Controller
{
    public function getIndex()
    {
        $_SERVER['ControllerDispatcherTestApi'] = $this->api;
        $_SERVER['ControllerDispatcherTestAuth'] = $this->auth;
        $_SERVER['ControllerDispatcherTestResponse'] = $this->response;

        return 'foo';
    }
}
