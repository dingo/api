<?php

use Mockery as m;
use Illuminate\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use Illuminate\Events\Dispatcher;
use Dingo\Api\Http\InternalRequest;

class RoutingRouterTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->exceptionHandler = m::mock('Dingo\Api\ExceptionHandler');

		$this->router = new Router(new Dispatcher);
		$this->router->setExceptionHandler($this->exceptionHandler);
		$this->router->setDefaultVersion('v1');
		$this->router->setVendor('testing');

		Response::setFormatters(['json' => new Dingo\Api\Http\ResponseFormat\JsonResponseFormat]);

		$transformer = m::mock('Dingo\Api\Transformer');
		$transformer->shouldReceive('transformableResponse')->andReturn(false);
		$transformer->shouldReceive('setRequest');

		Response::setTransformer($transformer);
	}


	public function tearDown()
	{
		Response::setFormatters([]);

		m::close();
	}


	public function testRegisteringApiRouteCollection()
	{
		$this->router->api(['version' => 'v1'], function() { $this->router->get('foo', function() { return 'bar'; }); });
		$request = Request::create('foo', 'GET');
		$this->assertInstanceOf('Dingo\Api\Routing\ApiRouteCollection', $this->router->getApiRouteCollection('v1'));
	}


	public function testRegisterApiRouteCollectionWithMultipleVersions()
	{
		$this->router->api(['version' => ['v1', 'v2']], function() { $this->router->get('foo', function() { return 'bar'; }); });
		$request = Request::create('foo', 'GET');
		$this->assertInstanceOf('Dingo\Api\Routing\ApiRouteCollection', $this->router->getApiRouteCollection('v1'));
		$this->assertInstanceOf('Dingo\Api\Routing\ApiRouteCollection', $this->router->getApiRouteCollection('v2'));
	}


	public function testRegisterApiRouteCollectionWithMultipleVersionsAndDifferentResponseForSameUri()
	{
		$this->router->api(['version' => 'v1'], function() { $this->router->get('foo', function() { return 'foo'; }); });
		$this->router->api(['version' => 'v2'], function() { $this->router->get('foo', function() { return 'bar'; }); });
		$request = Request::create('foo', 'GET');
		$request->headers->set('accept', 'application/vnd.testing.v1+json');
		$this->assertEquals('{"message":"foo"}', $this->router->dispatch($request)->getContent());
		$request->headers->set('accept', 'application/vnd.testing.v2+json');
		$this->assertEquals('{"message":"bar"}', $this->router->dispatch($request)->getContent());
	}


	public function testApiRouteCollectionOptionsApplyToRoutes()
	{
		$this->router->api(['version' => 'v1', 'protected' => true], function() { $this->router->get('foo', function() { return 'bar'; }); });
		$route = $this->router->getApiRouteCollection('v1')->getRoutes()[0];
		$this->assertTrue($route->getAction()['protected']);
	}


	/**
	 * @expectedException BadMethodCallException
	 */
	public function testRegisteringApiRouteGroupWithoutVersionThrowsException()
	{
		$this->router->api([], function(){});
	}


	public function testApiRoutesWithPrefix()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'foo/bar'], function()
		{
			$this->router->get('foo', function() { return 'foo'; });
		});

		$this->router->api(['version' => 'v2', 'prefix' => 'foo/bar'], function()
		{
			$this->router->get('foo', function() { return 'bar'; });
		});

		$request = Request::create('/foo/bar/foo', 'GET');
		$request->headers->set('accept', 'application/vnd.testing.v2+json');
		
		$this->assertEquals('{"message":"bar"}', $this->router->dispatch($request)->getContent());
	}


	public function testApiRoutesWithDomains()
	{
		$this->router->api(['version' => 'v1', 'domain' => 'foo.bar'], function()
		{
			$this->router->get('foo', function() { return 'foo'; });
		});

		$this->router->api(['version' => 'v2', 'domain' => 'foo.bar'], function()
		{
			$this->router->get('foo', function() { return 'bar'; });
		});

		$request = Request::create('http://foo.bar/foo', 'GET');
		$request->headers->set('accept', 'application/vnd.testing.v1+json');
		
		$this->assertEquals('{"message":"foo"}', $this->router->dispatch($request)->getContent());
	}


	public function testRouterDispatchesInternalRequests()
	{
		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('foo', function() { return 'bar'; });
		});

		$request = InternalRequest::create('foo', 'GET');
		$request->headers->set('accept', 'application/vnd.testing.v1+json');

		$this->assertEquals('{"message":"bar"}', $this->router->dispatch($request)->getContent());
	}


	public function testRouterFindsCollectionCurrentRequestIsTargeting()
	{
		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('/', function()
			{
				return 'foo';
			});
		});

		$this->router->api(['version' => 'v2'], function()
		{
			$this->router->get('/', function()
			{
				return 'bar';
			});
		});

		$request = Request::create('/', 'GET');
		$request->headers->set('accept', 'application/vnd.testing.v2+json');

		$response = $this->router->dispatch($request);
	}


	public function testAddingRouteFallsThroughToRouterCollection()
	{
		$this->router->get('foo', function() { return 'bar'; });

		$this->assertCount(1, $this->router->getRoutes());
	}


	public function testDispatchingRequestTargetsApiButFailsToFindRouteFallsThroughToRouterCollection()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'api'], function() {});

		$this->router->get('foo', function() { return 'bar'; });

		$this->assertEquals('bar', $this->router->dispatch(Request::create('foo', 'GET'))->getContent());
	}


	public function testRoutingToControllerWithWildcardScopes()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'api'], function()
		{
			$this->router->get('foo', 'WildcardScopeControllerStub@index');
		});

		$route = $this->router->getApiRouteCollection('v1')->getRoutes()[0];

		$this->assertEquals(['foo', 'bar'], $route->getAction()['scopes']);
	}


	public function testRoutingToControllerWithIndividualScopes()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'api'], function()
		{
			$this->router->get('foo', 'IndividualScopeControllerStub@index');
		});

		$route = $this->router->getApiRouteCollection('v1')->getRoutes()[0];

		$this->assertEquals(['foo', 'bar'], $route->getAction()['scopes']);
	}


	public function testRoutingToControllerMergesGroupScopes()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'api', 'scopes' => 'baz'], function()
		{
			$this->router->get('foo', 'WildcardScopeControllerStub@index');
		});

		$route = $this->router->getApiRouteCollection('v1')->getRoutes()[0];

		$this->assertEquals(['baz', 'foo', 'bar'], $route->getAction()['scopes']);
	}


	public function testRoutingToControllerWithProtectedMethod()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'api'], function()
		{
			$this->router->get('foo', 'ProtectedControllerStub@index');
		});

		$route = $this->router->getApiRouteCollection('v1')->getRoutes()[0];

		$this->assertTrue($route->getAction()['protected']);
	}


	public function testRoutingToControllerWithUnprotectedMethod()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'api', 'protected' => true], function()
		{
			$this->router->get('foo', 'UnprotectedControllerStub@index');
		});

		$route = $this->router->getApiRouteCollection('v1')->getRoutes()[0];

		$this->assertFalse($route->getAction()['protected']);
	}


	/**
	 * @expectedException RuntimeException
	 */
	public function testGettingUnkownApiCollectionThrowsException()
	{
		$this->router->getApiRouteCollection('v1');
	}


	public function testRouterCatchesHttpExceptionsAndCreatesResponse()
	{
		$exception = new Symfony\Component\HttpKernel\Exception\HttpException(404);

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(false);

		$this->router->api(['version' => 'v1'], function() use ($exception)
		{
			$this->router->get('foo', function() use ($exception) { throw $exception; });
		});

		$request = Request::create('foo', 'GET');
		$request->headers->set('accept', 'application/vnd.testing.v1+json');

		$response = $this->router->dispatch($request);
		
		$this->assertEquals(404, $response->getStatusCode());
		$this->assertEquals('{"message":"404 Not Found"}', $response->getContent());
	}


	public function testExceptionHandledAndResponseIsReturned()
	{
		$exception = new Symfony\Component\HttpKernel\Exception\HttpException(404, 'testing');

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(false);

		$response = $this->router->handleException($exception);

		$this->assertInstanceOf('Dingo\Api\Http\Response', $response);
		$this->assertEquals('testing', $response->getContent());
		$this->assertEquals(404, $response->getStatusCode());
	}


	public function testExceptionHandledAndResponseIsReturnedWithMissingMessage()
	{
		$exception = new Symfony\Component\HttpKernel\Exception\HttpException(404);

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(false);

		$response = $this->router->handleException($exception);

		$this->assertInstanceOf('Dingo\Api\Http\Response', $response);
		$this->assertEquals('404 Not Found', $response->getContent());
		$this->assertEquals(404, $response->getStatusCode());
	}


	public function testExceptionHandledAndResponseIsReturnedUsingResourceException()
	{
		$exception = new Dingo\Api\Exception\ResourceException('testing', ['foo' => 'bar']);

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(false);

		$response = $this->router->handleException($exception);
		$this->assertInstanceOf('Dingo\Api\Http\Response', $response);
		$this->assertEquals('{"message":"testing","errors":{}}', $response->getContent());
		$this->assertInstanceOf('Illuminate\Support\MessageBag', $response->getOriginalContent()['errors']);
		$this->assertEquals(422, $response->getStatusCode());
	}


	public function testExceptionHandledByExceptionHandler()
	{
		$exception = new Symfony\Component\HttpKernel\Exception\HttpException(404);

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(true);
		$this->exceptionHandler->shouldReceive('handle')->with($exception)->andReturn(new Response('testing', 404));

		$response = $this->router->handleException($exception);

		$this->assertInstanceOf('Dingo\Api\Http\Response', $response);
		$this->assertEquals('testing', $response->getContent());
		$this->assertEquals(404, $response->getStatusCode());
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
	 */
	public function testRouterCatchesHttpExceptionsAndRethrowsForInternalRequest()
	{
		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('foo', function() { throw new Symfony\Component\HttpKernel\Exception\HttpException(404); });
		});

		$this->router->dispatch(InternalRequest::create('foo', 'GET'));
	}


	public function testRouterIgnoresRouteGroupsWithAnApiPrefix()
	{
		$this->router->group(['prefix' => 'api'], function()
		{
			$this->router->get('foo', function() { return 'foo'; });
		});

		$this->assertEquals('foo', $this->router->dispatch(Request::create('api/foo', 'GET'))->getContent());
	}


	public function testRequestTargettingAnApiWithNoPrefixOrDomain()
	{
		$this->router->get('/', function() { return 'foo'; });

		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('foo', function() { return 'bar'; });
		});

		$this->assertFalse($this->router->requestTargettingApi(Request::create('/', 'GET')));
	}


	public function testRequestWithMultipleApisFindsTheCorrectApiRouteCollection()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'api'], function()
		{
			$this->router->get('foo', function() { return 'bar'; });
		});

		$this->router->api(['version' => 'v2', 'prefix' => 'api'], function()
		{
			$this->router->get('bar', function() { return 'baz'; });
		});

		$request = Request::create('api/bar', 'GET');
		$request->headers->set('accept', 'application/vnd.testing.v2+json');

		$this->assertEquals('{"message":"baz"}', $this->router->dispatch($request)->getContent());
	}


	public function testApiCollectionsWithPointReleaseVersions()
	{
		$this->router->api(['version' => 'v1.1', 'prefix' => 'api'], function()
		{
			$this->router->get('foo', function() { return 'bar'; });
		});

		$this->router->api(['version' => 'v2.0.1', 'prefix' => 'api'], function()
		{
			$this->router->get('bar', function() { return 'baz'; });
		});

		$request = Request::create('api/foo', 'GET');
		$request->headers->set('accept', 'application/vnd.testing.v1.1+json');

		$this->assertEquals('{"message":"bar"}', $this->router->dispatch($request)->getContent());

		$request = Request::create('api/bar', 'GET');
		$request->headers->set('accept', 'application/vnd.testing.v2.0.1+json');

		$this->assertEquals('{"message":"baz"}', $this->router->dispatch($request)->getContent());
	}


	public function testRouterDefaultsToDefaultVersionCollectionWhenNoAcceptHeader()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'api'], function()
		{
			$this->router->get('foo', function() { return 'bar'; });
		});

		$this->router->api(['version' => 'v2', 'prefix' => 'api'], function()
		{
			$this->router->get('foo', function() { return 'baz'; });
		});

		$request = Request::create('api/foo', 'GET');

		$this->router->setDefaultVersion('v2');
		$this->assertEquals('{"message":"baz"}', $this->router->dispatch($request)->getContent());
	}

    public function testRouterReturnsCorrectRoutesForArtisanCommand()
    {
        $this->router->api(['version' => 'v1'], function() { $this->router->get('foo', function() { return 'bar'; }); });
        $this->router->get('bar', function() { return 'baz'; });

        $this->assertCount(2, $this->router->getRoutes());
    }
}
