<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\Helpers;

class RoutingControllerStub
{
    use Helpers;

    public function __construct()
    {
        $this->scopes('baz|bing');
        $this->scopes('bob', ['except' => ['index']]);

        $this->authenticateWith('red|black', ['only' => 'index']);

        $this->rateLimit(10, 20);

        $this->throttle('Dingo\Api\Tests\Stubs\BasicThrottleStub');
    }

    public function index()
    {
        return 'foo';
    }

    public function getIndex()
    {
        return 'foo';
    }
}
