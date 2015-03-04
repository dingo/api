<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\ControllerTrait;
use Illuminate\Routing\Controller;

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
