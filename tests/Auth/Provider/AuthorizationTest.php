<?php

namespace Dingo\Api\Tests\Auth\Provider;

use Mockery as m;
use Illuminate\Http\Request;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Tests\Stubs\AuthorizationProviderStub;

class AuthorizationTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testExceptionThrownWhenAuthorizationHeaderIsInvalid()
    {
        $request = Request::create('GET', '/');

        (new AuthorizationProviderStub)->authenticate($request, m::mock('Dingo\Api\Routing\Route'));
    }
}
