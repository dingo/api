<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\ControllerTrait;
use Illuminate\Routing\Controller;

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
