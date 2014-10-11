<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\Controller;

class ProtectedControllerStub extends Controller
{
    public function __construct()
    {
        $this->protect('index');
    }

    public function index()
    {
        return 'foo';
    }
}
