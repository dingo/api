<?php

namespace Dingo\Api\Tests\Http;

use StdClass;
use Mockery as m;
use ReflectionClass;
use Dingo\Api\Http\Response;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Transformer\Binding;
use Dingo\Api\Http\Response\Format\Json;

class ResponseTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();

        $reflection = new ReflectionClass('Dingo\Api\Http\Response');

        $property = $reflection->getProperty('morphedCallbacks');

        $property->setAccessible(true);
        $property->setValue([]);

        $property = $reflection->getProperty('morphingCallbacks');

        $property->setAccessible(true);
        $property->setValue([]);
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
        $binding = new Binding(m::mock('Illuminate\Container\Container'), 'foo');

        $response = new Response('test', 200, [], $binding);
        $response->setMeta(['foo' => 'bar']);
        $response->meta('bing', 'bang');

        $this->assertEquals(['foo' => 'bar', 'bing' => 'bang'], $response->getMeta());
    }

    public function testBuildingWithCustomStatusCodeAndHeaders()
    {
        $response = new Response('test');
        $response->statusCode(302);
        $response->header('Foo', 'Bar');

        $this->assertEquals('Bar', $response->headers->get('Foo'));
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testChangingContentWithCallbacks()
    {
        Response::morphed(function ($content, Response $response) {
            $content['foo'] = 'bam!';

            return $content;
        });

        Response::addFormatter('json', new Json);

        $response = new Response(['foo' => 'bar']);

        $this->assertEquals('{"foo":"bam!"}', $response->morph('json')->getContent());
    }

    public function testChangingResponseHeadersWithCallbacks()
    {
        Response::morphing(function ($content, Response $response) {
            $response->headers->set('x-foo', 'bar');
        });

        Response::addFormatter('json', new Json);

        $response = new Response(['foo' => 'bar']);

        $this->assertEquals('bar', $response->morph('json')->headers->get('x-foo'));
    }
}
