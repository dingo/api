<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Routing\Route;
use Illuminate\Http\Request;
use Dingo\Api\Auth\Provider\Authorization;

class AuthorizationProviderStub extends Authorization
{
    public function authenticate(Request $request, Route $route)
    {
        $this->validateAuthorizationHeader($request);
    }

    public function getAuthorizationMethod()
    {
        return 'foo';
    }
}
