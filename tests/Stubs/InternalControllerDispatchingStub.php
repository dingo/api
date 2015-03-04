<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\ControllerTrait;
use Illuminate\Routing\Controller;

class InternalControllerDispatchingStub extends Controller
{
    use ControllerTrait;

    public function index()
    {
        return 'foo';
    }
}
