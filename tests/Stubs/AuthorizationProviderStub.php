<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Auth\AuthorizationProvider;
use Dingo\Api\Routing\Route;
use Illuminate\Http\Request;

class AuthorizationProviderStub extends AuthorizationProvider
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
