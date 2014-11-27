<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Routing\Controller;
use Dingo\Api\Routing\ControllerTrait;

class WildcardScopeControllerStub extends Controller
{
    use ControllerTrait;

    public function __construct()
    {
        $this->scopes(['foo', 'bar']);
    }

    public function index()
    {
        return 'foo';
    }
}
