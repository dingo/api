<?php

namespace Dingo\Api\Tests\Http;

use StdClass;
use Mockery as m;
use Dingo\Api\Http\Response;
use PHPUnit_Framework_TestCase;

class ResponseTest extends PHPUnit_Framework_TestCase
{
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
        $binding = m::mock('Dingo\Api\Transformer\Binding');
        $binding->shouldReceive('setMeta')->once()->with(['foo' => 'bar']);
        $binding->shouldReceive('addMeta')->once()->with('foo', 'bar');

        $response = new Response('test', 200, [], $binding);
        $response->setMeta(['foo' => 'bar']);
        $response->meta('foo', 'bar');
    }

    public function testBuildingWithCustomStatusCodeAndHeaders()
    {
        $response = new Response('test');
        $response->statusCode(302);
        $response->header('Foo', 'Bar');

        $this->assertEquals('Bar', $response->headers->get('Foo'));
        $this->assertEquals(302, $response->getStatusCode());
    }
}
