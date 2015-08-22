<?php

namespace Dingo\Api\Tests\Routing\Adapter;

use Mockery as m;
use Dingo\Api\Http;
use Dingo\Api\Routing\Router;
use PHPUnit_Framework_TestCase;
use Illuminate\Container\Container;
use Dingo\Api\Tests\Stubs\MiddlewareStub;

abstract class BaseAdapterTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new Container;
        $this->container['Illuminate\Container\Container'] = $this->container;
        $this->container['api.auth'] = new MiddlewareStub;
        $this->container['api.limiting'] = new MiddlewareStub;
        $this->container['request'] = new Http\Request;

        $this->adapter = $this->getAdapterInstance();
        $this->exception = m::mock('Dingo\Api\Exception\Handler');
        $this->router = new Router($this->adapter, new Http\Parser\Accept('vnd', 'api', 'v1', 'json'), $this->exception, $this->container, null, null);

        Http\Response::setFormatters(['json' => new Http\Response\Format\Json]);
    }

    public function tearDown()
    {
        m::close();
    }

    abstract public function getAdapterInstance();

    protected function createRequest($uri, $method, array $headers = [])
    {
        $request = Http\Request::create($uri, $method);

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        return $this->container['request'] = $request;
    }

    public function testBasicRouteVersions()
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
            $this->router->post('foo', function () {
                return 'posted';
            });
            $this->router->patch('foo', function () {
                return 'patched';
            });
            $this->router->delete('foo', function () {
                return 'deleted';
            });
            $this->router->put('foo', function () {
                return 'put';
            });
            $this->router->options('foo', function () {
                return 'options';
            });
        });

        $this->router->group(['version' => 'v2'], function () {
            $this->router->get('foo', ['version' => 'v3', function () {
                return 'bar';
            }]);
        });

        $this->createRequest('/', 'GET');

        $this->assertArrayHasKey('v1', $this->router->getRoutes(), 'No routes were registered for version 1.');
        $this->assertArrayHasKey('v2', $this->router->getRoutes(), 'No routes were registered for version 2.');
        $this->assertArrayHasKey('v3', $this->router->getRoutes(), 'No routes were registered for version 3.');

        $request = $this->createRequest('/foo', 'GET', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertEquals('foo', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo/', 'GET', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertEquals('foo', $this->router->dispatch($request)->getContent(), 'Could not dispatch request with trailing slash.');

        $request = $this->createRequest('/foo', 'GET', ['accept' => 'application/vnd.api.v2+json']);
        $this->assertEquals('bar', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'GET', ['accept' => 'application/vnd.api.v3+json']);
        $this->assertEquals('bar', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'POST', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertEquals('posted', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'PATCH', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertEquals('patched', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'DELETE', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertEquals('deleted', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'PUT', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertEquals('put', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'options', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertEquals('options', $this->router->dispatch($request)->getContent());
    }

    public function testAdapterDispatchesRequestsThroughRouter()
    {
        $this->container['request'] = Http\Request::create('/foo', 'GET');

        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $response = $this->router->dispatch($this->container['request']);

        $this->assertEquals('foo', $response->getContent());
    }

    public function testRoutesWithPrefix()
    {
        $this->router->version('v1', ['prefix' => 'foo/bar'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->version('v2', ['prefix' => 'foo/bar'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $request = $this->createRequest('/foo/bar/foo', 'GET', ['accept' => 'application/vnd.api.v2+json']);
        $this->assertEquals('bar', $this->router->dispatch($request)->getContent(), 'Router could not dispatch prefixed routes.');
    }

    public function testRoutesWithDomains()
    {
        $this->router->version('v1', ['domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->version('v2', ['domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $request = $this->createRequest('http://foo.bar/foo', 'GET', ['accept' => 'application/vnd.api.v2+json']);
        $this->assertEquals('bar', $this->router->dispatch($request)->getContent(), 'Router could not dispatch domain routes.');
    }

    public function testPointReleaseVersions()
    {
        $this->router->version('v1.1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->version('v2.0.1', function () {
            $this->router->get('bar', function () {
                return 'bar';
            });
        });

        $request = $this->createRequest('/foo', 'GET', ['accept' => 'application/vnd.api.v1.1+json']);
        $this->assertEquals('foo', $this->router->dispatch($request)->getContent(), 'Router does not support point release versions.');

        $request = $this->createRequest('/bar', 'GET', ['accept' => 'application/vnd.api.v2.0.1+json']);
        $this->assertEquals('bar', $this->router->dispatch($request)->getContent(), 'Router does not support point release versions.');
    }

    public function testRoutingControllers()
    {
        $this->router->version('v1', ['namespace' => 'Dingo\Api\Tests\Stubs'], function () {
            $this->router->controllers([
                'bar' => 'RoutingControllerStub',
            ]);
        });

        $request = $this->createRequest('/bar/index', 'GET', ['accept' => 'application/vnd.api.v1+json']);

        $this->assertEquals('foo', $this->router->dispatch($request)->getContent(), 'Router did not register controller correctly.');

        $this->router->version('v2', function () {
            $this->router->controllers([
                'bar' => 'Dingo\Api\Tests\Stubs\RoutingControllerStub',
            ]);
        });

        $request = $this->createRequest('/bar/index', 'GET', ['accept' => 'application/vnd.api.v1+json']);

        $this->assertEquals('foo', $this->router->dispatch($request)->getContent(), 'Router did not register controller correctly.');
    }

    public function testRoutingResources()
    {
        $this->router->version('v1', ['namespace' => 'Dingo\Api\Tests\Stubs'], function () {
            $this->router->resources([
                'bar' => ['RoutingControllerStub', ['only' => ['index']]],
            ]);
        });

        $request = $this->createRequest('/bar', 'GET', ['accept' => 'application/vnd.api.v1+json']);

        $this->assertEquals('foo', $this->router->dispatch($request)->getContent(), 'Router did not register controller correctly.');
    }
}
