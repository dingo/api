<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\Controller;

class ControllerStub extends Controller
{
    public function getRoutableMethod()
    {
        return 'foo';
    }
}
