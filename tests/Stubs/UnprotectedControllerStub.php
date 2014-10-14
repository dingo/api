<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Routing\Controller;
use Dingo\Api\Routing\ControllerTrait;

class UnprotectedControllerStub extends Controller
{
    use ControllerTrait;

    public function __construct()
    {
        $this->unprotect('index');
    }

    public function index()
    {
        return 'foo';
    }
}
