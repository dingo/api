<?php

namespace Dingo\Api\Tests\Http;

use Dingo\Api\Event\ResponseIsMorphing;
use Dingo\Api\Event\ResponseWasMorphed;
use Dingo\Api\Http\Response;
use Dingo\Api\Http\Response\Format\Json;
use Dingo\Api\Tests\BaseTestCase;
use Dingo\Api\Transformer\Binding;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Mockery as m;
use StdClass;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class ResponseTest extends BaseTestCase
{
    /**
     * @var EventDispatcher
     */
    protected $events;

    public function setUp(): void
    {
        Response::setEventDispatcher($this->events = new EventDispatcher);
    }

    public function testGettingInvalidFormatterThrowsException()
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Unable to format response according to Accept header.');

        Response::getFormatter('json');
    }

    public function testNonCastableObjectsSetAsOriginalContent()
    {
        $object = new StdClass;
        $object->id = 'test';

        $response = new Response($object);

        $this->assertNull($response->getContent());
        $this->assertSame($object, $response->getOriginalContent());
    }

    public function testAddingAndSettingMetaCallsUnderlyingTransformerBinding()
    {
        $binding = new Binding(m::mock(Container::class), 'foo');

        $response = new Response('test', 200, [], $binding);
        $response->setMeta(['foo' => 'bar']);
        $response->meta('bing', 'bang');

        $this->assertSame(['foo' => 'bar', 'bing' => 'bang'], $response->getMeta());
    }

    public function testBuildingWithCustomStatusCodeAndHeaders()
    {
        $response = new Response('test');
        $response->statusCode(302);
        $response->header('Foo', 'Bar');

        $this->assertSame('Bar', $response->headers->get('Foo'));
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testChangingContentWithEvents()
    {
        $this->events->listen(ResponseWasMorphed::class, function ($event) {
            $event->content['foo'] = 'bam!';
        });

        Response::addFormatter('json', new Json);

        $response = new Response(['foo' => 'bar']);

        $this->assertSame('{"foo":"bam!"}', $response->morph('json')->getContent());

        $this->events->forget(ResponseWasMorphed::class);
    }

    public function testChangingResponseHeadersWithEvents()
    {
        $this->events->listen(ResponseIsMorphing::class, function ($event) {
            $event->response->headers->set('x-foo', 'bar');
        });

        Response::addFormatter('json', new Json);

        $response = new Response(['foo' => 'bar']);

        $this->assertSame('bar', $response->morph('json')->headers->get('x-foo'));

        $this->events->forget(ResponseIsMorphing::class);
    }
}
