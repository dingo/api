<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Auth\AuthorizationProvider;

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
