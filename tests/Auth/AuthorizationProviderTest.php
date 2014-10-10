<?php

namespace Dingo\Api\Tests\Auth;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Tests\Stubs\AuthorizationProviderStub;

class AuthorizationProviderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testExceptionThrownWhenAuthorizationHeaderIsInvalid()
    {
        $request = Request::create('GET', '/');

        (new AuthorizationProviderStub)->authenticate($request, new Route('GET', '/', []));
    }
}
