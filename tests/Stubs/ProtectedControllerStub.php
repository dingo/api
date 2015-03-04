<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\ControllerTrait;
use Illuminate\Routing\Controller;

class ProtectedControllerStub extends Controller
{
    use ControllerTrait;

    public function __construct()
    {
        $this->protect('index');
    }

    public function index()
    {
        return 'foo';
    }
}
