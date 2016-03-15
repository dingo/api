<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Routing\Controller;

class RoutingControllerOtherStub extends Controller
{
    public function find()
    {
        return 'baz';
    }
}
