<?php

namespace Dingo\Api\Tests\Routing;

use Mockery as m;
use Dingo\Api\Http;
use Dingo\Api\Routing\Router;
use PHPUnit_Framework_TestCase;
use Illuminate\Container\Container;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RouterTest extends Adapter\BaseAdapterTest
{
    public function getAdapterInstance()
    {
        return $this->container->make('Dingo\Api\Tests\Stubs\RoutingAdapterStub');
    }

    public function testRouteOptionsMergeCorrectly()
    {
        $this->router->version('v1', ['protected' => true, 'scopes' => 'foo|bar'], function () {
            $this->router->get('foo', ['scopes' => ['baz'], function () {
                $this->assertTrue(
                    $this->router->getCurrentRoute()->isProtected(),
                    'Route was not protected but should be.'
                );
                $this->assertEquals(
                    ['foo', 'bar', 'baz'],
                    $this->router->getCurrentRoute()->getScopes(),
                    'Router did not merge string based group scopes with route based array scopes.'
                );
            }]);

            $this->router->get('bar', ['protected' => false, function () {
                $this->assertFalse(
                    $this->router->getCurrentRoute()->isProtected(),
                    'Route was protected but should not be.'
                );
            }]);

            $this->router->get('baz', ['protected' => false, function () {
                $this->assertEquals(
                    ['foo', 'bar'],
                    $this->router->getCurrentRoute()->getScopes(),
                    'Router did not merge string based group scopes with route.'
                );
            }]);
        });

        $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json']);
        $this->router->dispatch($request);

        $request = $this->createRequest('bar', 'GET', ['accept' => 'application/vnd.api.v1+json']);
        $this->router->dispatch($request);

        $request = $this->createRequest('baz', 'GET', ['accept' => 'application/vnd.api.v1+json']);
        $this->router->dispatch($request);

        $this->router->version('v2', ['providers' => 'foo', 'throttle' => 'Bar', 'namespace' => 'Dingo\Api\Tests'], function () {
            $this->router->get('foo', 'Stubs\RoutingControllerStub@index');
        });

        $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v2+json']);
        $this->router->dispatch($request);

        $route = $this->router->getCurrentRoute();

        $this->assertEquals(['baz', 'bing'], $route->scopes());
        $this->assertEquals(['foo', 'red', 'black'], $route->getAuthProviders());
        $this->assertTrue($route->isProtected());
        $this->assertEquals(10, $route->getRateLimit());
        $this->assertEquals(20, $route->getRateExpiration());
        $this->assertEquals('Zippy', $route->getThrottle());
    }

    /**
     * @expectedException RuntimeException
     * @expectedMessage A version is required for an API group definition.
     */
    public function testNoGroupVersionThrowsException()
    {
        $this->router->group([], function () {
            //
        });
    }

    public function testRouterPreparesNotModifiedResponse()
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $this->router->setConditionalRequest(false);

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json'])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('bar', $response->getContent());

        $this->router->setConditionalRequest(true);

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('"'.md5('bar').'"', $response->getETag());
        $this->assertEquals('bar', $response->getContent());

        $request = $this->createRequest('foo', 'GET', [
            'if-none-match' => '"'.md5('bar').'"',
            'accept' => 'application/vnd.api.v1+json'
        ]);

        $response = $this->router->dispatch($request);

        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals('"'.md5('bar').'"', $response->getETag());
        $this->assertEquals(null, $response->getContent());

        $request = $this->createRequest('foo', 'GET', [
            'if-none-match' => '123456789',
            'accept' => 'application/vnd.api.v1+json'
        ]);

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('"'.md5('bar').'"', $response->getETag());
        $this->assertEquals('bar', $response->getContent());
    }

    public function testRouterHandlesExistingEtag()
    {
        $this->router->version('v1', ['conditional_request' => true], function () {
            $this->router->get('foo', function () {
                $response = new Http\Response('bar');
                $response->setEtag('custom-etag');

                return $response;
            });
        });

        $response = $this->router->dispatch(
            $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json'])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('"custom-etag"', $response->getETag());
        $this->assertEquals('bar', $response->getContent());
    }

    public function testRouterHandlesCustomEtag()
    {
        $this->router->version('v1', ['conditional_request' => true], function () {
            $this->router->get('foo', function () {
                $response = new Http\Response('bar');
                $response->setEtag('custom-etag');

                return $response;
            });
        });

        $response = $this->router->dispatch(
            $this->createRequest('foo', 'GET', [
                'if-none-match' => '"custom-etag"',
                'accept' => 'application/vnd.api.v1+json'
            ])
        );

        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals('"custom-etag"', $response->getETag());
        $this->assertEquals(null, $response->getContent());
    }

    public function testExceptionsAreHandledByExceptionHandler()
    {
        $exception = new HttpException(400);

        $this->router->version('v1', function () use ($exception) {
            $this->router->get('foo', function () use ($exception) {
                throw $exception;
            });
        });

        $this->exception->shouldReceive('handle')->once()->with($exception)->andReturn(new Http\Response('exception'));

        $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json']);

        $this->assertEquals('exception', $this->router->dispatch($request)->getContent(), 'Router did not delegate exception handling.');
    }

    public function testNoAcceptHeaderUsesDefaultVersion()
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->assertEquals('foo', $this->router->dispatch($this->createRequest('foo', 'GET'))->getContent(), 'Router does not default to default version.');
    }

    public function testRoutesAddedToCorrectVersions()
    {
        $this->router->version('v1', ['domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $this->router->version('v2', ['domain' => 'foo.bar'], function () {
            $this->router->get('bar', function () {
                return 'baz';
            });
        });

        $this->createRequest('/', 'GET');

        $this->assertCount(1, $this->router->getRoutes()['v1'], 'Routes were not added to the correct versions.');
    }

    public function testUnsuccessfulResponseThrowsHttpException()
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return new Http\Response('Failed!', 400);
            });
        });

        $request = $this->createRequest('foo', 'GET');

        $this->exception->shouldReceive('handle')->with(m::type('Symfony\Component\HttpKernel\Exception\HttpException'))->andReturn(new Http\Response('Failed!'));

        $this->assertEquals('Failed!', $this->router->dispatch($request)->getContent(), 'Router did not throw and handle a HttpException.');
    }

    public function testRouteMiddlewaresAreUnsetAndMovedIfManuallySetOnRoutes()
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', ['middleware' => 'foo|api.auth', function () use (&$middleware) {
                $route = $this->router->getCurrentRoute();

                $this->assertEquals(['api.auth', 'api.limiting', 'foo'], $route->getAction()['middleware']);

                return 'foo';
            }]);
        });

        $request = $this->createRequest('foo', 'GET');

        $this->router->dispatch($request);
    }

    public function testGroupNamespacesAreConcatenated()
    {
        $this->router->version('v1', ['namespace' => 'Dingo\Api'], function () {
            $this->router->group(['namespace' => 'Tests\Stubs'], function () {
                $this->router->get('foo', 'RoutingControllerStub@getIndex');
            });
        });

        $request = $this->createRequest('foo', 'GET');

        $this->assertEquals('foo', $this->router->dispatch($request)->getContent(), 'Router did not concatenate controller namespace correctly.');
    }
}
