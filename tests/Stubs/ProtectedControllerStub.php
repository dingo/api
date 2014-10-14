<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Routing\Controller;
use Dingo\Api\Routing\ControllerTrait;

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
