<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\Controller;

class WildcardScopeControllerStub extends Controller
{
    public function __construct()
    {
        $this->scope(['foo', 'bar']);
    }

    public function index()
    {
        return 'foo';
    }
}
