<?php

namespace Dingo\Api\tests\Http;

use Dingo\Api\Http\ResponseBuilder;
use Mockery;
use PHPUnit_Framework_TestCase;

class ResponseBuilderTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testAddingAndSettingMetaCallsUnderlyingTransformerBinding()
    {
        $binding = Mockery::mock('Dingo\Api\Transformer\Binding');
        $binding->shouldReceive('setMeta')->once()->with(['foo' => 'bar']);
        $binding->shouldReceive('addMeta')->once()->with('foo', 'bar');

        $builder = new ResponseBuilder('test', $binding);
        $builder->setMeta(['foo' => 'bar']);
        $builder->meta('foo', 'bar');
    }

    public function testBuildingWithCustomStatusCodeAndHeaders()
    {
        $builder = new ResponseBuilder('test');
        $builder->statusCode(302);
        $builder->header('Foo', 'Bar');

        $response = $builder->build();

        $this->assertEquals('Bar', $response->headers->get('Foo'));
        $this->assertEquals(302, $response->getStatusCode());
    }
}
