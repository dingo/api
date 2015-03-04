<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\ControllerTrait;
use Illuminate\Routing\Controller;

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
