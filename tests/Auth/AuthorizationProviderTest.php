<?php

namespace Dingo\Api\tests\Auth;

use Dingo\Api\Routing\Route;
use Dingo\Api\Tests\Stubs\AuthorizationProviderStub;
use Illuminate\Http\Request;
use PHPUnit_Framework_TestCase;

class AuthorizationProviderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testExceptionThrownWhenAuthorizationHeaderIsInvalid()
    {
        $request = Request::create('GET', '/');

        (new AuthorizationProviderStub())->authenticate($request, new Route('GET', '/', []));
    }
}
