<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Routing\Controller;
use Dingo\Api\Routing\ControllerTrait;

class IndividualScopeControllerStub extends Controller
{
    use ControllerTrait;

    public function __construct()
    {
        $this->scopes(['foo', 'bar'], 'index');
    }

    public function index()
    {
        //
    }
}
