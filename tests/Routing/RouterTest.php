<?php

namespace Dingo\Api\Tests\Routing;

use Mockery as m;
use Dingo\Api\Http;
use Dingo\Api\Routing\Router;
use Illuminate\Container\Container;
use Dingo\Api\Tests\Stubs\ThrottleStub;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RouterTest extends Adapter\BaseAdapterTest
{
    public function getAdapterInstance()
    {
        return $this->container->make(\Dingo\Api\Tests\Stubs\RoutingAdapterStub::class);
    }

    public function getContainerInstance()
    {
        return new Container;
    }

    public function testRouteOptionsMergeCorrectly()
    {
        $this->router->version('v1', ['scopes' => 'foo|bar'], function () {
            $this->router->get('foo', ['scopes' => ['baz'], function () {
                $this->assertSame(
                    ['foo', 'bar', 'baz'],
                    $this->router->getCurrentRoute()->getScopes(),
                    'Router did not merge string based group scopes with route based array scopes.'
                );
            }]);

            $this->router->get('baz', function () {
                $this->assertSame(
                    ['foo', 'bar'],
                    $this->router->getCurrentRoute()->getScopes(),
                    'Router did not merge string based group scopes with route.'
                );
            });
        });

        $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json']);
        $this->router->dispatch($request);

        $request = $this->createRequest('baz', 'GET', ['accept' => 'application/vnd.api.v1+json']);
        $this->router->dispatch($request);

        $this->router->version('v2', ['providers' => 'foo', 'throttle' => new ThrottleStub(['limit' => 10, 'expires' => 15]), 'namespace' => \Dingo\Api\Tests::class], function () {
            $this->router->get('foo', 'Stubs\RoutingControllerStub@index');
        });

        $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v2+json']);
        $this->router->dispatch($request);

        $route = $this->router->getCurrentRoute();

        $this->assertSame(['baz', 'bing'], $route->scopes());
        $this->assertSame(['foo', 'red', 'black'], $route->getAuthenticationProviders());
        $this->assertSame(10, $route->getRateLimit());
        $this->assertSame(20, $route->getRateLimitExpiration());
        $this->assertInstanceOf(\Dingo\Api\Tests\Stubs\BasicThrottleStub::class, $route->getThrottle());
    }

    public function testGroupAsPrefixesRouteAs()
    {
        $this->router->version('v1', ['as' => 'api'], function ($api) {
            $api->get('users', ['as' => 'users', function () {
                return 'foo';
            }]);
        });

        $routes = $this->router->getRoutes('v1');

        $this->assertInstanceOf(\Dingo\Api\Routing\Route::class, $routes->getByName('api.users'));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedMessage A version is required for an API group definition.
     */
    public function testNoGroupVersionThrowsException()
    {
        $this->router->group([], function () {
            //
        });
    }

    public function testMatchRoutes()
    {
        $this->router->version('v1', function ($api) {
            $api->match(['get', 'post'], 'foo', function () {
                return 'bar';
            });
        });

        $this->router->setConditionalRequest(false);

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json'])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bar', $response->getContent());

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'POST', ['accept' => 'application/vnd.api.v1+json'])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bar', $response->getContent());
    }

    public function testAnyRoutes()
    {
        $this->router->version('v1', function ($api) {
            $api->any('foo', function () {
                return 'bar';
            });
        });

        $this->router->setConditionalRequest(false);

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json'])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bar', $response->getContent());

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'POST', ['accept' => 'application/vnd.api.v1+json'])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bar', $response->getContent());

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'PATCH', ['accept' => 'application/vnd.api.v1+json'])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bar', $response->getContent());

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'DELETE', ['accept' => 'application/vnd.api.v1+json'])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bar', $response->getContent());
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

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bar', $response->getContent());

        $this->router->setConditionalRequest(true);

        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('"'.sha1('bar').'"', $response->getETag());
        $this->assertSame('bar', $response->getContent());

        $request = $this->createRequest('foo', 'GET', [
            'if-none-match' => '"'.sha1('bar').'"',
            'accept' => 'application/vnd.api.v1+json',
        ]);

        $response = $this->router->dispatch($request);

        $this->assertSame(304, $response->getStatusCode());
        $this->assertSame('"'.sha1('bar').'"', $response->getETag());
        $this->assertEmpty($response->getContent());

        $request = $this->createRequest('foo', 'GET', [
            'if-none-match' => '123456789',
            'accept' => 'application/vnd.api.v1+json',
        ]);

        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('"'.sha1('bar').'"', $response->getETag());
        $this->assertSame('bar', $response->getContent());
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

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('"custom-etag"', $response->getETag());
        $this->assertSame('bar', $response->getContent());
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
                'accept' => 'application/vnd.api.v1+json',
            ])
        );

        $this->assertSame(304, $response->getStatusCode());
        $this->assertSame('"custom-etag"', $response->getETag());
        $this->assertEmpty($response->getContent());
    }

    public function testExceptionsAreHandledByExceptionHandler()
    {
        $exception = new HttpException(400);

        $this->router->version('v1', function () use ($exception) {
            $this->router->get('foo', function () use ($exception) {
                throw $exception;
            });
        });

        $this->exception->shouldReceive('report')->once()->with($exception);
        $this->exception->shouldReceive('handle')->once()->with($exception)->andReturn(new Http\Response('exception'));

        $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json']);

        $this->assertSame('exception', $this->router->dispatch($request)->getContent(), 'Router did not delegate exception handling.');
    }

    public function testNoAcceptHeaderUsesDefaultVersion()
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->assertSame('foo', $this->router->dispatch($this->createRequest('foo', 'GET'))->getContent(), 'Router does not default to default version.');
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

        $this->exception->shouldReceive('handle')->with(m::type(\Symfony\Component\HttpKernel\Exception\HttpException::class))->andReturn(new Http\Response('Failed!'));

        $this->assertSame('Failed!', $this->router->dispatch($request)->getContent(), 'Router did not throw and handle a HttpException.');
    }

    public function testGroupNamespacesAreConcatenated()
    {
        $this->router->version('v1', ['namespace' => \Dingo\Api::class], function () {
            $this->router->group(['namespace' => 'Tests\Stubs'], function () {
                $this->router->get('foo', 'RoutingControllerStub@getIndex');
            });
        });

        $request = $this->createRequest('foo', 'GET');

        $this->assertSame('foo', $this->router->dispatch($request)->getContent(), 'Router did not concatenate controller namespace correctly.');
    }

    public function testCurrentRouteName()
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', ['as' => 'foo', function () {
                return 'foo';
            }]);
        });

        $request = $this->createRequest('foo', 'GET');

        $this->router->dispatch($request);

        $this->assertFalse($this->router->currentRouteNamed('bar'));
        $this->assertTrue($this->router->currentRouteNamed('foo'));
        $this->assertTrue($this->router->is('*'));
        $this->assertFalse($this->router->is('b*'));
        $this->assertTrue($this->router->is('b*', 'f*'));
    }

    public function testCurrentRouteAction()
    {
        $this->router->version('v1', ['namespace' => \Dingo\Api\Tests\Stubs::class], function () {
            $this->router->get('foo', 'RoutingControllerStub@getIndex');
        });

        $request = $this->createRequest('foo', 'GET');

        $this->router->dispatch($request);

        $this->assertFalse($this->router->currentRouteUses('foo'));
        $this->assertTrue($this->router->currentRouteUses(\Dingo\Api\Tests\Stubs\RoutingControllerStub::class.'@getIndex'));
        $this->assertFalse($this->router->uses('foo*'));
        $this->assertTrue($this->router->uses('*'));
        $this->assertTrue($this->router->uses(\Dingo\Api\Tests\Stubs\RoutingControllerStub::class.'@*'));
    }

    public function testRoutePatternsAreAppliedCorrectly()
    {
        $adapter = $this->adapter;
        $adapter->pattern('bar', '[0-9]+');

        $this->router = new Router($adapter, $this->exception, $this->container, null, null);
        $this->router->version('v1', function ($api) {
            $api->any('foo/{bar}', function () {
                return 'bar';
            });
        });

        $this->router->setConditionalRequest(false);

        $this->exception->shouldReceive('report')->once()->with(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->exception->shouldReceive('handle')->with(m::type(\Symfony\Component\HttpKernel\Exception\HttpException::class))->andReturn(new Http\Response('Not Found!', 404));

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo/abc', 'GET', ['accept' => 'application/vnd.api.v1+json'])
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found!', $response->getContent());
    }
}
