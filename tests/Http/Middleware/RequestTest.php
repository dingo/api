<?php

namespace Dingo\Api\Tests\Http\Middleware;

use Mockery as m;
use Illuminate\Http\Request;
use Dingo\Api\Http\Validator;
use Dingo\Api\Http\Validation;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Tests\Stubs\ApplicationStub;
use Dingo\Api\Http\Parser\Accept as AcceptParser;
use Dingo\Api\Http\Middleware\Request as RequestMiddleware;

class RequestTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->app = new ApplicationStub;
        $this->router = m::mock('Dingo\Api\Routing\Router');
        $this->validator = new Validator($this->app);

        $this->middleware = new RequestMiddleware($this->app, $this->router, $this->validator, []);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testNoPrefixOrDomainDoesNotMatch()
    {
        $this->app['Dingo\Api\Http\Validation\Domain'] = new Validation\Domain(null);
        $this->app['Dingo\Api\Http\Validation\Prefix'] = new Validation\Prefix(null);
        $this->app['Dingo\Api\Http\Validation\Accept'] = new Validation\Accept(new AcceptParser('api', 'v1', 'json'));

        $request = Request::create('foo', 'GET');

        $this->middleware->handle($request, function ($handled) use ($request) {
            $this->assertEquals($handled, $request);
        });
    }

    public function testPrefixMatchesAndSendsRequestThroughRouter()
    {
        $this->app['Dingo\Api\Http\Validation\Domain'] = new Validation\Domain(null);
        $this->app['Dingo\Api\Http\Validation\Prefix'] = new Validation\Prefix('/');
        $this->app['Dingo\Api\Http\Validation\Accept'] = new Validation\Accept(new AcceptParser('api', 'v1', 'json'));

        $request = Request::create('foo', 'GET');

        $this->router->shouldReceive('dispatch')->once();

        $this->middleware->handle($request, function () {
            //
        });

        $this->app['Dingo\Api\Http\Validation\Domain'] = new Validation\Domain(null);
        $this->app['Dingo\Api\Http\Validation\Prefix'] = new Validation\Prefix('bar');
        $this->app['Dingo\Api\Http\Validation\Accept'] = new Validation\Accept(new AcceptParser('api', 'v1', 'json'));

        $request = Request::create('bar/foo', 'GET');

        $this->router->shouldReceive('dispatch')->once();

        $this->middleware->handle($request, function () {
            //
        });

        $request = Request::create('bing/bar/foo', 'GET');

        $this->middleware->handle($request, function ($handled) use ($request) {
            $this->assertEquals($handled, $request);
        });
    }

    public function testDomainMatchesAndSendsRequestThroughRouter()
    {
        $this->app['Dingo\Api\Http\Validation\Domain'] = new Validation\Domain('foo.bar');
        $this->app['Dingo\Api\Http\Validation\Prefix'] = new Validation\Prefix(null);
        $this->app['Dingo\Api\Http\Validation\Accept'] = new Validation\Accept(new AcceptParser('api', 'v1', 'json'));

        $request = Request::create('http://foo.bar/baz', 'GET');

        $this->router->shouldReceive('dispatch')->once();

        $this->middleware->handle($request, function () {
            //
        });

        $request = Request::create('http://bing.foo.bar/baz', 'GET');

        $this->middleware->handle($request, function ($handled) use ($request) {
            $this->assertEquals($handled, $request);
        });
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedMessage Accept header could not be properly parsed because of a strict matching process.
     */
    public function testParserThrowsExceptionWhenMatchingStrictAcceptHeader()
    {
        $this->app['Dingo\Api\Http\Validation\Domain'] = new Validation\Domain('foo.bar');
        $this->app['Dingo\Api\Http\Validation\Prefix'] = new Validation\Prefix(null);
        $this->app['Dingo\Api\Http\Validation\Accept'] = new Validation\Accept(new AcceptParser('api', 'v1', 'json'), true);

        $request = Request::create('http://foo.bar/baz', 'GET');

        $this->middleware->handle($request, function () {
            //
        });
    }
}
