<?php

namespace Dingo\Api\Tests;

use Mockery;
use Dingo\Api\Properties;
use Dingo\Api\Dispatcher;
use Illuminate\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Auth\Authenticator;
use Illuminate\Container\Container;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\RouteCollection;
use Dingo\Api\Exception\InternalHttpException;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Dingo\Api\Http\ResponseFormat\JsonResponseFormat;
use Illuminate\Support\Facades\Request as RequestFacade;

class DispatcherTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $config = new Properties('v1', null, null, 'test');
        $this->container = new Container;
        $this->container['request'] = Request::create('/', 'GET');
        $url = new UrlGenerator(new RouteCollection, $this->container['request']);

        $this->router = new Router(new EventDispatcher, $config);
        $this->auth = new Authenticator($this->router, $this->container, []);
        $this->dispatcher = new Dispatcher($this->container, new Filesystem, $url, $this->router, $this->auth, $config);

        Response::setFormatters(['json' => new JsonResponseFormat]);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testInternalRequests()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('test', function () {
                return 'foo';
            });

            $this->router->post('test', function () {
                return 'bar';
            });

            $this->router->put('test', function () {
                return 'baz';
            });

            $this->router->patch('test', function () {
                return 'yin';
            });

            $this->router->delete('test', function () {
                return 'yang';
            });
        });

        $this->assertEquals('foo', $this->dispatcher->get('test'));
        $this->assertEquals('bar', $this->dispatcher->post('test'));
        $this->assertEquals('baz', $this->dispatcher->put('test'));
        $this->assertEquals('yin', $this->dispatcher->patch('test'));
        $this->assertEquals('yang', $this->dispatcher->delete('test'));
    }

    public function testInternalRequestWithVersionAndParameters()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('test', function () { return 'test'; });
        });

        $this->assertEquals('test', $this->dispatcher->version('v1')->with(['foo' => 'bar'])->get('test'));
    }

    public function testInternalRequestWithPrefix()
    {
        $this->router->api(['version' => 'v1', 'prefix' => 'baz'], function () {
            $this->router->get('test', function () {
                return 'test';
            });
        });

        $this->assertEquals('test', $this->dispatcher->get('test'));
    }

    public function testInternalRequestWithDomain()
    {
        $this->router->api(['version' => 'v1', 'domain' => 'foo.bar'], function () {
            $this->router->get('test', function () {
                return 'test';
            });
        });

        $this->assertEquals('test', $this->dispatcher->get('test'));
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testInternalRequestThrowsException()
    {
        $this->router->api(['version' => 'v1'], function () {
            //
        });

        $this->dispatcher->get('test');
    }

    /**
     * @expectedException \Dingo\Api\Exception\InternalHttpException
     */
    public function testInternalRequestThrowsExceptionWhenResponseIsNotOkay()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('test', function () {
                return new \Illuminate\Http\Response('test', 401);
            });
        });

        $this->dispatcher->get('test');
    }

    public function testInternalExceptionContainsResponseObject()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('test', function () {
                return new \Illuminate\Http\Response('test', 401);
            });
        });

        try {
            $this->dispatcher->get('test');
        } catch (InternalHttpException $exception) {
            $this->assertInstanceOf('Illuminate\Http\Response', $exception->getResponse());
            $this->assertEquals('test', $exception->getResponse()->getContent());
        }
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testPretendingToBeUserWithInvalidParameterThrowsException()
    {
        $this->dispatcher->be('foo');
    }

    public function testPretendingToBeUserForSingleRequest()
    {
        $user = Mockery::mock('Illuminate\Database\Eloquent\Model');

        $this->router->api(['version' => 'v1'], function () use ($user) {
            $this->router->get('test', function () use ($user) {
                $this->assertEquals($user, $this->auth->user());
            });
        });

        $this->dispatcher->be($user)->once()->get('test');
    }

    public function testInternalRequestUsingRouteName()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('test', ['as' => 'test', function () {
                return 'foo';
            }]);

            $this->router->get('test/{foo}', ['as' => 'parameters', function ($parameter) {
                return $parameter;
            }]);
        });

        $this->assertEquals('foo', $this->dispatcher->route('test'));
        $this->assertEquals('bar', $this->dispatcher->route('parameters', 'bar'));
    }

    public function testInternalRequestUsingControllerAction()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('foo', 'Dingo\Api\Tests\Stubs\InternalControllerDispatchingStub@index');
        });

        $this->assertEquals('foo', $this->dispatcher->action('Dingo\Api\Tests\Stubs\InternalControllerDispatchingStub@index'));
    }

    public function testInternalRequestUsingRouteNameAndControllerAction()
    {
        $this->router->api(['version' => 'v1', 'prefix' => 'api'], function () {
            $this->router->get('foo', ['as' => 'foo', function () { return 'foo'; }]);
            $this->router->get('bar', 'Dingo\Api\Tests\Stubs\InternalControllerDispatchingStub@index');
        });

        $this->assertEquals('foo', $this->dispatcher->route('foo'));
        $this->assertEquals('foo', $this->dispatcher->action('Dingo\Api\Tests\Stubs\InternalControllerDispatchingStub@index'));
    }

    public function testInternalRequestWithMultipleVersionsCallsCorrectVersion()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->api(['version' => ['v2', 'v3']], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $this->assertEquals('foo', $this->dispatcher->version('v1')->get('foo'));
        $this->assertEquals('bar', $this->dispatcher->version('v2')->get('foo'));
        $this->assertEquals('bar', $this->dispatcher->version('v3')->get('foo'));
    }

    public function testInternalRequestWithNestedInternalRequest()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->get('foo', function () {
                return 'foo'.$this->dispatcher->version('v2')->get('foo');
            });
        });

        $this->router->api(['version' => 'v2'], function () {
            $this->router->get('foo', function () {
                return 'bar'.$this->dispatcher->version('v3')->get('foo');
            });
        });

        $this->router->api(['version' => 'v3'], function () {
            $this->router->get('foo', function () {
                return 'baz';
            });
        });

        $this->assertEquals('foobarbaz', $this->dispatcher->get('foo'));
    }

    public function testRequestStackIsMaintained()
    {
        $this->router->api(['version' => 'v1', 'prefix' => 'api'], function () {
            $this->router->post('foo', function () {
                $this->assertEquals('bar', $this->router->getCurrentRequest()->input('foo'));
                $this->dispatcher->with(['foo' => 'baz'])->post('bar');
                $this->assertEquals('bar', $this->router->getCurrentRequest()->input('foo'));
            });

            $this->router->post('bar', function () {
                $this->assertEquals('baz', $this->router->getCurrentRequest()->input('foo'));
                $this->dispatcher->with(['foo' => 'bazinga'])->post('baz');
                $this->assertEquals('baz', $this->router->getCurrentRequest()->input('foo'));
            });

            $this->router->post('baz', function () {
                $this->assertEquals('bazinga', $this->router->getCurrentRequest()->input('foo'));
            });
        });

        $this->dispatcher->with(['foo' => 'bar'])->post('foo');
    }

    public function testRouteStackIsMaintained()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->post('foo', ['as' => 'foo', function () {
                $this->assertEquals('foo', $this->router->currentRouteName());
                $this->dispatcher->post('bar');
                $this->assertEquals('foo', $this->router->currentRouteName());
            }]);

            $this->router->post('bar', ['as' => 'bar', function () {
                $this->assertEquals('bar', $this->router->currentRouteName());
                $this->dispatcher->post('baz');
                $this->assertEquals('bar', $this->router->currentRouteName());
            }]);

            $this->router->post('baz', ['as' => 'bazinga', function () {
                $this->assertEquals('bazinga', $this->router->currentRouteName());
            }]);
        });

        $this->dispatcher->post('foo');
    }

    public function testSendingJsonPayload()
    {
        $this->router->api(['version' => 'v1'], function () {
            $this->router->post('foo', function () {
                $this->assertEquals('jason', $this->router->getCurrentRequest()->json('username'));
            });

            $this->router->post('bar', function () {
                $this->assertEquals('mat', $this->router->getCurrentRequest()->json('username'));
            });
        });

        $this->dispatcher->json(['username' => 'jason'])->post('foo');
        $this->dispatcher->json('{"username":"mat"}')->post('bar');
    }

    public function testInternalRequestsToDifferentDomains()
    {
        $this->router->api(['version' => ['v1', 'v2'], 'domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'v1 and v2 on domain foo.bar';
            });
        });

        $this->router->api(['version' => 'v1', 'domain' => 'foo.baz'], function () {
            $this->router->get('foo', function () {
                return 'v1 on domain foo.baz';
            });
        });

        $this->router->api(['version' => 'v2', 'domain' => 'foo.baz'], function () {
            $this->router->get('foo', function () {
                return 'v2 on domain foo.baz';
            });
        });

        $this->assertEquals('v1 and v2 on domain foo.bar', $this->dispatcher->on('foo.bar')->version('v2')->get('foo'));
        $this->assertEquals('v1 on domain foo.baz', $this->dispatcher->on('foo.baz')->get('foo'));
        $this->assertEquals('v2 on domain foo.baz', $this->dispatcher->on('foo.baz')->version('v2')->get('foo'));
    }

    public function testRequestingRawResponse()
    {
        $this->router->api('v1', function () {
            $this->router->get('foo', function () {
                return ['foo' => 'bar'];
            });
        });

        $response = $this->dispatcher->raw()->get('foo');

        $this->assertInstanceOf('Dingo\Api\Http\Response', $response);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals(['foo' => 'bar'], $response->getOriginalContent());
    }

    public function testUsingRequestFacadeDoesNotCacheRequestInstance()
    {
        RequestFacade::setFacadeApplication($this->container);

        $this->router->api('v1', function () {
            $this->router->get('foo', function () {
                return RequestFacade::input('foo');
            });
        });

        $this->assertNull(RequestFacade::input('foo'));

        $response = $this->dispatcher->with(['foo' => 'bar'])->get('foo');

        $this->assertEquals('bar', $response);
        $this->assertNull(RequestFacade::input('foo'));
    }
}
