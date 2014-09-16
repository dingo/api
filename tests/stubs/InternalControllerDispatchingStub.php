<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Routing\Controller;

class InternalControllerDispatchingStub extends Controller
{
    public function index()
    {
        return 'foo';
    }
}
