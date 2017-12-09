<?php

namespace Dingo\Api\Tests\Http\Middleware;

use Mockery as m;
use Dingo\Api\Http\Request;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\Validation;
use PHPUnit\Framework\TestCase;
use Dingo\Api\Exception\Handler;
use Dingo\Api\Http\RequestValidator;
use Dingo\Api\Http\Validation\Accept;
use Dingo\Api\Http\Validation\Domain;
use Dingo\Api\Http\Validation\Prefix;
use Dingo\Api\Tests\Stubs\ApplicationStub;
use Dingo\Api\Http\Parser\Accept as AcceptParser;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Dingo\Api\Contract\Http\Request as RequestContract;
use Dingo\Api\Http\Middleware\Request as RequestMiddleware;

class RequestTest extends TestCase
{
    protected $app;
    protected $router;
    protected $validator;
    protected $handler;
    protected $events;
    protected $middleware;

    public function setUp()
    {
        $this->app = new ApplicationStub;
        $this->router = m::mock(Router::class);
        $this->validator = new RequestValidator($this->app);
        $this->handler = m::mock(Handler::class);
        $this->events = new EventDispatcher($this->app);

        $this->app->alias(Request::class, RequestContract::class);

        $this->middleware = new RequestMiddleware($this->app, $this->handler, $this->router, $this->validator, $this->events);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testNoPrefixOrDomainDoesNotMatch()
    {
        $this->app[Domain::class] = new Validation\Domain(null);
        $this->app[Prefix::class] = new Validation\Prefix(null);
        $this->app[Accept::class] = new Validation\Accept(new AcceptParser('vnd', 'api', 'v1', 'json'));

        $request = Request::create('foo', 'GET');

        $this->middleware->handle($request, function ($handled) use ($request) {
            $this->assertSame($handled, $request);
        });
    }

    public function testPrefixMatchesAndSendsRequestThroughRouter()
    {
        $this->app[Domain::class] = new Validation\Domain(null);
        $this->app[Prefix::class] = new Validation\Prefix('/');
        $this->app[Accept::class] = new Validation\Accept(new AcceptParser('vnd', 'api', 'v1', 'json'));

        $request = IlluminateRequest::create('foo', 'GET');

        $this->router->shouldReceive('dispatch')->once();

        $this->middleware->handle($request, function () {
            //
        });

        $this->app[Domain::class] = new Validation\Domain(null);
        $this->app[Prefix::class] = new Validation\Prefix('bar');
        $this->app[Accept::class] = new Validation\Accept(new AcceptParser('vnd', 'api', 'v1', 'json'));

        $request = IlluminateRequest::create('bar/foo', 'GET');

        $this->router->shouldReceive('dispatch')->once();

        $this->middleware->handle($request, function () {
            //
        });

        $request = IlluminateRequest::create('bing/bar/foo', 'GET');

        $this->middleware->handle($request, function ($handled) use ($request) {
            $this->assertSame($handled, $request);
        });
    }

    public function testDomainMatchesAndSendsRequestThroughRouter()
    {
        $this->app[Domain::class] = new Validation\Domain('foo.bar');
        $this->app[Prefix::class] = new Validation\Prefix(null);
        $this->app[Accept::class] = new Validation\Accept(new AcceptParser('vnd', 'api', 'v1', 'json'));

        $request = IlluminateRequest::create('http://foo.bar/baz', 'GET');

        $this->router->shouldReceive('dispatch')->once();

        $this->middleware->handle($request, function () {
            //
        });

        $request = IlluminateRequest::create('http://bing.foo.bar/baz', 'GET');

        $this->middleware->handle($request, function ($handled) use ($request) {
            $this->assertSame($handled, $request);
        });
    }
}
