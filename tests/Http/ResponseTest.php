<?php

namespace Dingo\Api\Tests\Http;

use StdClass;
use Mockery as m;
use Dingo\Api\Http\Response;
use PHPUnit\Framework\TestCase;
use Dingo\Api\Transformer\Binding;
use Illuminate\Container\Container;
use Dingo\Api\Event\ResponseIsMorphing;
use Dingo\Api\Event\ResponseWasMorphed;
use Dingo\Api\Http\Response\Format\Json;
use Illuminate\Events\Dispatcher as EventDispatcher;

class ResponseTest extends TestCase
{
    public function setUp()
    {
        Response::setEventDispatcher($this->events = new EventDispatcher);
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     * @expectedExceptionMessage Unable to format response according to Accept header.
     */
    public function testGettingInvalidFormatterThrowsException()
    {
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
