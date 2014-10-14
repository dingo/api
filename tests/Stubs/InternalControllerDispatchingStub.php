<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Routing\Controller;
use Dingo\Api\Routing\ControllerTrait;

class InternalControllerDispatchingStub extends Controller
{
    use ControllerTrait;

    public function index()
    {
        return 'foo';
    }
}
