<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\Controller;

class IndividualScopeControllerStub extends Controller
{
    public function __construct()
    {
        $this->scope(['foo', 'bar'], 'index');
    }

    public function index()
    {
        
    }
}
