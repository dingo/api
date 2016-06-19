<?php

namespace Dingo\Api\Tests;

use Mockery as m;
use Dingo\Api\Http;
use Dingo\Api\Auth\Auth;
use Dingo\Api\Dispatcher;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Router;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Tests\Stubs\UserStub;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Dingo\Api\Tests\Stubs\MiddlewareStub;
use Dingo\Api\Tests\Stubs\TransformerStub;
use Dingo\Api\Tests\Stubs\RoutingAdapterStub;
use Dingo\Api\Tests\Stubs\UserTransformerStub;
use Dingo\Api\Exception\InternalHttpException;
use Dingo\Api\Transformer\Factory as TransformerFactory;
use Illuminate\Support\Facades\Request as RequestFacade;

class DispatcherTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var TransformerFactory
     */
    protected $transformerFactory;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var Router;
     */
    protected $router;

    public function setUp()
    {
        $this->container = new Container;
        $this->container['request'] = Request::create('/', 'GET');
        $this->container['api.auth'] = new MiddlewareStub;
        $this->container['api.limiting'] = new MiddlewareStub;

        Http\Request::setAcceptParser(new Http\Parser\Accept('vnd', 'api', 'v1', 'json'));

        $this->transformerFactory = new TransformerFactory($this->container, new TransformerStub);

        $this->adapter = new RoutingAdapterStub;
        $this->exception = m::mock('Dingo\Api\Exception\Handler');
        $this->router = new Router($this->adapter, $this->exception, $this->container, null, null);

        $this->auth = new Auth($this->router, $this->container, []);
        $this->dispatcher = new Dispatcher($this->container, new Filesystem, $this->router, $this->auth);

        $this->dispatcher->setSubtype('api');
        $this->dispatcher->setStandardsTree('vnd');
        $this->dispatcher->setDefaultVersion('v1');
        $this->dispatcher->setDefaultFormat('json');

        Http\Response::setFormatters(['json' => new Http\Response\Format\Json]);
        Http\Response::setTransformer($this->transformerFactory);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testInternalRequests()
    {
        $this->router->version('v1', function () {
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
        $this->router->version('v1', function () {
            $this->router->get('test', function () {
                return 'test';
            });
        });

        $this->assertEquals('test', $this->dispatcher->version('v1')->with(['foo' => 'bar'])->get('test'));
    }

    public function testInternalRequestWithPrefix()
    {
        $this->router->version('v1', ['prefix' => 'baz'], function () {
            $this->router->get('test', function () {
                return 'test';
            });
        });

        $this->assertEquals('test', $this->dispatcher->get('baz/test'));

        $this->dispatcher->setPrefix('baz');

        $this->assertEquals('test', $this->dispatcher->get('test'));
    }

    public function testInternalRequestWithDomain()
    {
        $this->router->version('v1', ['domain' => 'foo.bar'], function () {
            $this->router->get('test', function () {
                return 'test';
            });
        });

        $this->assertEquals('test', $this->dispatcher->get('http://foo.bar/test'));

        $this->dispatcher->setDefaultDomain('foo.bar');

        $this->assertEquals('test', $this->dispatcher->get('test'));
    }

    /**
     * @expectedException \Dingo\Api\Exception\InternalHttpException
     */
    public function testInternalRequestThrowsExceptionWhenResponseIsNotOkay()
    {
        $this->router->version('v1', function () {
            $this->router->get('test', function () {
                return new \Illuminate\Http\Response('test', 403);
            });
        });

        $this->dispatcher->get('test');
    }

    public function testInternalExceptionContainsResponseObject()
    {
        $this->router->version('v1', function () {
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

    public function testThrowingHttpExceptionFallsThroughRouter()
    {
        $this->router->version('v1', function () {
            $this->router->get('test', function () {
                throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException;
            });
        });

        $passed = false;

        try {
            $this->dispatcher->get('test');
        } catch (\Symfony\Component\HttpKernel\Exception\GoneHttpException $exception) {
            $passed = true;
        }

        $this->assertTrue($passed);
    }

    public function testPretendingToBeUserForSingleRequest()
    {
        $user = m::mock('Illuminate\Database\Eloquent\Model');

        $this->router->version('v1', function () use ($user) {
            $this->router->get('test', function () use ($user) {
                $this->assertEquals($user, $this->auth->user());
            });
        });

        $this->dispatcher->be($user)->once()->get('test');
    }

    public function testInternalRequestWithMultipleVersionsCallsCorrectVersion()
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->version(['v2', 'v3'], function () {
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
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'foo'.$this->dispatcher->version('v2')->get('foo');
            });
        });

        $this->router->version('v2', function () {
            $this->router->get('foo', function () {
                return 'bar'.$this->dispatcher->version('v3')->get('foo');
            });
        });

        $this->router->version('v3', function () {
            $this->router->get('foo', function () {
                return 'baz';
            });
        });

        $this->assertEquals('foobarbaz', $this->dispatcher->get('foo'));
    }

    public function testRequestStackIsMaintained()
    {
        $this->router->version('v1', ['prefix' => 'api'], function () {
            $this->router->post('foo', function () {
                $this->assertEquals('bar', $this->router->getCurrentRequest()->input('foo'));
                $this->dispatcher->with(['foo' => 'baz'])->post('api/bar');
                $this->assertEquals('bar', $this->router->getCurrentRequest()->input('foo'));
            });

            $this->router->post('bar', function () {
                $this->assertEquals('baz', $this->router->getCurrentRequest()->input('foo'));
                $this->dispatcher->with(['foo' => 'bazinga'])->post('api/baz');
                $this->assertEquals('baz', $this->router->getCurrentRequest()->input('foo'));
            });

            $this->router->post('baz', function () {
                $this->assertEquals('bazinga', $this->router->getCurrentRequest()->input('foo'));
            });
        });

        $this->dispatcher->with(['foo' => 'bar'])->post('api/foo');
    }

    public function testRouteStackIsMaintained()
    {
        $this->router->version('v1', function () {
            $this->router->post('foo', ['as' => 'foo', function () {
                $this->assertEquals('foo', $this->router->getCurrentRoute()->getName());
                $this->dispatcher->post('bar');
                $this->assertEquals('foo', $this->router->getCurrentRoute()->getName());
            }]);

            $this->router->post('bar', ['as' => 'bar', function () {
                $this->assertEquals('bar', $this->router->getCurrentRoute()->getName());
                $this->dispatcher->post('baz');
                $this->assertEquals('bar', $this->router->getCurrentRoute()->getName());
            }]);

            $this->router->post('baz', ['as' => 'bazinga', function () {
                $this->assertEquals('bazinga', $this->router->getCurrentRoute()->getName());
            }]);
        });

        $this->dispatcher->post('foo');
    }

    public function testSendingJsonPayload()
    {
        $this->router->version('v1', function () {
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
        $this->router->version(['v1', 'v2'], ['domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'v1 and v2 on domain foo.bar';
            });
        });

        $this->router->version('v1', ['domain' => 'foo.baz'], function () {
            $this->router->get('foo', function () {
                return 'v1 on domain foo.baz';
            });
        });

        $this->router->version('v2', ['domain' => 'foo.baz'], function () {
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
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return ['foo' => 'bar'];
            });
        });

        $response = $this->dispatcher->raw()->get('foo');

        $this->assertInstanceOf('Dingo\Api\Http\Response', $response);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals(['foo' => 'bar'], $response->getOriginalContent());
    }

    public function testRequestingRawResponseWithTransformers()
    {
        $instance = null;

        $this->router->version('v1', function () use (&$instance) {
            $this->router->get('foo', function () use (&$instance) {
                return $instance = new UserStub('Jason');
            });
        });

        $this->transformerFactory->register(UserStub::class, UserTransformerStub::class);

        $response = $this->dispatcher->raw()->get('foo');

        $this->assertInstanceOf('Dingo\Api\Http\Response', $response);
        $this->assertEquals('{"name":"Jason"}', $response->getContent());
        $this->assertEquals($instance, $response->getOriginalContent());
    }

    public function testUsingRequestFacadeDoesNotCacheRequestInstance()
    {
        RequestFacade::setFacadeApplication($this->container);

        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return RequestFacade::input('foo');
            });
        });

        $this->assertNull(RequestFacade::input('foo'));

        $response = $this->dispatcher->with(['foo' => 'bar'])->get('foo');

        $this->assertEquals('bar', $response);
        $this->assertNull(RequestFacade::input('foo'));
    }

    /**
     * Test model serialization with transformer binding.
     */
    public function testModelSerializationWithTransformers()
    {
        $modelMock = $this
            ->getMockBuilder('\Illuminate\Database\Eloquent\Model')
            ->getMock();
        $modelMock
            ->method('getTable')
            ->willReturn('test');

        $modelMock
            ->expects($this->never())
            ->method('toJson');
        $modelMock
            ->expects($this->never())
            ->method('toArray');

        $transformerMock = $this
            ->getMockBuilder('\League\Fractal\TransformerAbstract')
            ->setMethods(['transform'])
            ->disableOriginalConstructor()
            ->getMock();
        $transformerMock
            ->expects($this->once())
            ->method('transform')
            ->willReturn(['data' => 'test']);

        $bindingMock = $this
            ->getMockBuilder('\Dingo\Api\Transformer\Binding')
            ->disableOriginalConstructor()
            ->getMock();
        $bindingMock
            ->method('resolveTransformer')
            ->willReturn($transformerMock);

        $this->router->version('v1', function () use ($modelMock, $bindingMock) {
            $this->router->get('foo', function () use ($modelMock, $bindingMock) {
                return new Http\Response($modelMock, 200, [], $bindingMock);
            });
        });

        $this->transformerFactory->register(get_class($modelMock), $transformerMock);

        $response = $this->dispatcher->raw()->get('foo');

        $this->assertInstanceOf('Dingo\Api\Http\Response', $response);
        $this->assertEquals('{"data":"test"}', $response->getContent());
        $this->assertEquals($modelMock, $response->getOriginalContent());
    }
}
