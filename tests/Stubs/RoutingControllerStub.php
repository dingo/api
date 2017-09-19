<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\Helpers;
use Illuminate\Routing\Controller;

class RoutingControllerStub extends Controller
{
    use Helpers;

    public function __construct()
    {
        $this->scopes('baz|bing');
        $this->scopes('bob', ['except' => ['index']]);

        $this->authenticateWith('red|black', ['only' => 'index']);

        $this->rateLimit(10, 20);

        $this->throttle(BasicThrottleStub::class);
    }

    public function index()
    {
        return 'foo';
    }

    public function show()
    {
        return 'bar';
    }

    public function getIndex()
    {
        return 'foo';
    }
}
