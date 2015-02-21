<?php

namespace Dingo\Api\Tests\Routing;

use Mockery as m;
use Dingo\Api\Properties;
use Illuminate\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use PHPUnit_Framework_TestCase;
use Illuminate\Events\Dispatcher;
use Dingo\Api\Http\ResponseBuilder;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Http\ResponseFormat\JsonResponseFormat;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RouterTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->events = new Dispatcher;
        $this->config = new Properties('v1', null, null, 'testing', 'json', false);

        $this->router = new Router($this->events, $this->config);

        Response::setFormatters(['json' => new JsonResponseFormat]);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testRegisteringApiRoutes()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $this->router->api('v2', function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $this->assertInstanceOf('Dingo\Api\Routing\RouteCollection', $this->router->getApiGroups()->getByVersion('v1'));
        $this->assertInstanceOf('Dingo\Api\Routing\RouteCollection', $this->router->getApiGroups()->getByVersion('v2'));
    }

    public function testRegisterApiRoutesWithMultipleVersions()
    {
        $this->router->api(['version' => ['v1', 'v2']], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->api(['version' => 'v2'], function () {
            $this->router->get('bar', function () {
                return 'bar';
            });
        });

        $request = Request::create('foo', 'GET');
        $request->headers->set('accept', 'application/vnd.testing.v2+json');
        $this->assertEquals('foo', $this->router->dispatch($request)->getContent());

        $request = Request::create('bar', 'GET');
        $request->headers->set('accept', 'application/vnd.testing.v2+json');
        $this->assertEquals('bar', $this->router->dispatch($request)->getContent());
    }

    public function testRegisterApiRoutesWithDifferentResponseForSameUri()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->api(['version' => 'v2'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $request = Request::create('foo', 'GET');
        $request->headers->set('accept', 'application/vnd.testing.v1+json');
        $this->assertEquals('foo', $this->router->dispatch($request)->getContent());

        $request->headers->set('accept', 'application/vnd.testing.v2+json');
        $this->assertEquals('bar', $this->router->dispatch($request)->getContent());
    }

    public function testApiRouteCollectionOptionsApplyToRoutes()
    {
        $this->router->api(['version' => 'v1', 'protected' => true], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });

            $this->router->get('bar', ['protected' => true, function () {
                return 'bar';
            }]);

            $this->router->get('baz', ['protected' => false, function () {
                return 'bar';
            }]);
        });

        $routes = $this->router->getApiGroups()->getByVersion('v1')->getRoutes();
        $this->assertTrue($routes[0]->isProtected());
        $this->assertTrue($routes[1]->isProtected());
        $this->assertFalse($routes[2]->isProtected());
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testRegisteringApiRouteGroupWithoutVersionThrowsException()
    {
        $this->router->api([], function () {});
    }

    public function testApiRoutesWithPrefix()
    {
        $this->router->api(['version' => 'v1', 'prefix' => 'foo/bar'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->api(['version' => 'v2', 'prefix' => 'foo/bar'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $request = Request::create('/foo/bar/foo', 'GET');
        $request->headers->set('accept', 'application/vnd.testing.v2+json');
        $this->assertEquals('bar', $this->router->dispatch($request)->getContent());
    }

    public function testApiRoutesWithDomains()
    {
        $this->router->api(['version' => 'v1', 'domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->api(['version' => 'v2', 'domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $request = Request::create('http://foo.bar/foo', 'GET');
        $request->headers->set('accept', 'application/vnd.testing.v2+json');
        $this->assertEquals('bar', $this->router->dispatch($request)->getContent());
    }

    public function testRouterDispatchesInternalRequests()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $request = InternalRequest::create('foo', 'GET');
        $request->headers->set('accept', 'application/vnd.testing.v1+json');
        $this->assertEquals('foo', $this->router->dispatch($request)->getContent());
    }

    public function testAddingRouteFallsThroughToRouterCollection()
    {
        $this->router->get('foo', function () {
            return 'foo';
        });

        $this->assertCount(1, $this->router->getRoutes());
    }

    public function testRouterPreparesNotModifiedIlluminateResponse()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('foo', function () { return 'bar'; });
        });

        $request = Request::create('foo', 'GET');
        $request->headers->set('accept', 'application/vnd.testing.v1+json');
        $this->router->setConditionalRequest(false);
        $response = $this->router->dispatch($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('bar', $response->getContent());

        $request = Request::create('foo', 'GET');
        $request->headers->set('accept', 'application/vnd.testing.v1+json');
        $this->router->setConditionalRequest(true);
        $response = $this->router->dispatch($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('"'.md5('bar').'"', $response->getETag());
        $this->assertEquals('bar', $response->getContent());

        $request = Request::create('foo', 'GET');
        $request->headers->set('If-None-Match', '"'.md5('bar').'"', true);
        $request->headers->set('accept', 'application/vnd.testing.v1+json');
        $this->router->setConditionalRequest(true);
        $response = $this->router->dispatch($request);
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals('"'.md5('bar').'"', $response->getETag());
        $this->assertEquals(null, $response->getContent());

        $request = Request::create('foo', 'GET');
        $request->headers->set('If-None-Match', '0123456789', true);
        $request->headers->set('accept', 'application/vnd.testing.v1+json');
        $this->router->setConditionalRequest(true);
        $response = $this->router->dispatch($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('"'.md5('bar').'"', $response->getETag());
        $this->assertEquals('bar', $response->getContent());
    }

    public function testRouterSkipNotModifiedResponseOutsideApi()
    {
        $this->router->group([], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $request = Request::create('foo', 'GET');
        $this->router->setConditionalRequest(true);
        $response = $this->router->dispatch($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->headers->has('ETag'));
        $this->assertEquals('bar', $response->getContent());
    }

    public function testRouterHandlesExistingEtag()
    {
        $this->router->api(['version' => 'v1', 'conditional_request' => true], function () {
            $this->router->get('foo', function () {
                $response = new Response('bar');
                $response->setEtag('custom-etag');

                return $response;
            });
        });

        $request = Request::create('foo', 'GET');
        $request->headers->set('accept', 'application/vnd.testing.v1+json');
        $response = $this->router->dispatch($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('"custom-etag"', $response->getETag());
        $this->assertEquals('bar', $response->getContent());
    }

    public function testRouterHandlesCustomEtag()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('foo', function () {
                $response = new Response('bar');
                $response->setEtag('custom-etag');

                return $response;
            });
        });

        $request = Request::create('foo', 'GET');
        $request->headers->set('If-None-Match', '"custom-etag"', true);
        $request->headers->set('accept', 'application/vnd.testing.v1+json');
        $this->router->setConditionalRequest(true);
        $response = $this->router->dispatch($request);
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals('"custom-etag"', $response->getETag());
        $this->assertEquals(null, $response->getContent());
    }

    public function testRouterFiresExceptionEvent()
    {
        $exception = new ResourceException;

        $this->router->api(['version' => 'v1'], function () use ($exception) {
            $this->router->get('foo', function () use ($exception) {
                throw $exception;
            });
        });

        $this->events->listen('router.exception', function ($exception) {
            $this->assertInstanceOf('Dingo\Api\Exception\ResourceException', $exception);

            return new \Dingo\Api\Http\Response(null, $exception->getStatusCode());
        });

        $request = Request::create('foo', 'GET');
        $request->headers->set('accept', 'application/vnd.testing.v1+json');
        $response = $this->router->dispatch($request);
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testRouterCatchesHttpExceptionsAndRethrowsForInternalRequest()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('foo', function () {
                throw new HttpException(404);
            });
        });

        $request = InternalRequest::create('foo', 'GET');
        $this->router->dispatch($request);
    }

    public function testRouterIgnoresRouteGroupsWithAnApiPrefix()
    {
        $this->router->group(['prefix' => 'api'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $request = Request::create('api/foo', 'GET');
        $this->assertEquals('foo', $this->router->dispatch($request)->getContent());
    }

    public function testRequestTargettingAnApiWithNoPrefixOrDomain()
    {
        $this->router->get('/', function () {
            return 'foo';
        });

        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $request = Request::create('/', 'GET');
        $this->assertFalse($this->router->isApiRequest($request));

        $request = Request::create('foo', 'GET');
        $this->assertTrue($this->router->isApiRequest($request));
    }

    public function testRequestWithMultipleApisFindsTheCorrectApiRouteCollection()
    {
        $this->router->api(['version' => 'v1', 'prefix' => 'api'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->api(['version' => 'v2', 'prefix' => 'api'], function () {
            $this->router->get('bar', function () {
                return 'bar';
            });
        });

        $request = Request::create('api/bar', 'GET');
        $request->headers->set('accept', 'application/vnd.testing.v2+json');
        $this->assertEquals('bar', $this->router->dispatch($request)->getContent());
    }

    public function testApiCollectionsWithPointReleaseVersions()
    {
        $this->router->api(['version' => 'v1.1', 'prefix' => 'api'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->api(['version' => 'v2.0.1', 'prefix' => 'api'], function () {
            $this->router->get('bar', function () {
                return 'bar';
            });
        });

        $request = Request::create('api/foo', 'GET');
        $request->headers->set('accept', 'application/vnd.testing.v1.1+json');
        $this->assertEquals('foo', $this->router->dispatch($request)->getContent());

        $request = Request::create('api/bar', 'GET');
        $request->headers->set('accept', 'application/vnd.testing.v2.0.1+json');
        $this->assertEquals('bar', $this->router->dispatch($request)->getContent());
    }

    public function testRouterDefaultsToDefaultVersionCollectionWhenNoAcceptHeader()
    {
        $this->router->api(['version' => 'v1', 'prefix' => 'api'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->api(['version' => 'v2', 'prefix' => 'api'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $this->config->setVersion('v2');
        $request = Request::create('api/foo', 'GET');
        $this->assertEquals('bar', $this->router->dispatch($request)->getContent());
    }

    public function testRouterPreparesResponseBuilderResponse()
    {
        $request = Request::create('foo', 'GET');

        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('foo', function () {
                return new ResponseBuilder('bar');
            });
        });

        $this->assertEquals('bar', $this->router->dispatch($request)->getContent());
    }

    /**
     * @expectedException \Dingo\Api\Exception\InvalidAcceptHeaderException
     */
    public function testRouterThrowsExceptionWhenInvalidAcceptHeaderWithStrict()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $this->router->setStrict(true);
        $this->router->dispatch(Request::create('foo', 'GET'));
    }

    public function testRouterParsesScopesFromStringsToArrays()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->group(['scopes' => 'foo'], function () {
                $this->router->get('foo', function () {
                    return 'bar';
                });
            });

            $this->router->get('bar', ['scopes' => 'bar', function () {
                return 'baz';
            }]);
        });

        $this->assertEquals(['foo'], $this->router->getApiGroups()->getByVersion('v1')->getRoutes()[0]->scopes());
        $this->assertEquals(['bar'], $this->router->getApiGroups()->getByVersion('v1')->getRoutes()[1]->scopes());
    }

    public function testRoutesOnlyAddedToSpecifiedCollection()
    {
        $this->router->api(['version' => 'v1', 'domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $this->router->api(['version' => 'v2', 'domain' => 'foo.bar'], function () {
            $this->router->get('bar', function () {
                return 'baz';
            });
        });

        $this->assertCount(1, $this->router->getApiGroups()->getByVersion('v1')->getRoutes());
    }
}
