<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\Controller;

class UnprotectedControllerStub extends Controller
{
    public function __construct()
    {
        $this->unprotect('index');
    }

    public function index()
    {
        return 'foo';
    }
}
