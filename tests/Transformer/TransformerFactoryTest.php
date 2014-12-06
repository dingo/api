<?php

namespace Dingo\Api\Tests\Transformer;

use Mockery;
use PHPUnit_Framework_TestCase;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Dingo\Api\Tests\Stubs\UserStub;
use Dingo\Api\Tests\Stubs\TransformerStub;
use Dingo\Api\Tests\Stubs\UserContractStub;
use Dingo\Api\Transformer\TransformerFactory;
use Dingo\Api\Tests\Stubs\UserTransformerStub;

class TransformerFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $container = new Container;
        $container['request'] = Mockery::mock('Illuminate\Http\Request');

        $this->factory = new TransformerFactory($container, new TransformerStub);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testResponseIsTransformable()
    {
        $this->assertFalse($this->factory->transformableResponse(new UserStub, new UserTransformerStub));

        $this->factory->register('Dingo\Api\Tests\Stubs\UserStub', new UserTransformerStub);

        $this->assertTrue($this->factory->transformableResponse(new UserStub, new UserTransformerStub));
    }

    public function testResponseIsTransformableType()
    {
        $this->assertFalse($this->factory->transformableType(['foo' => 'bar']));
        $this->assertTrue($this->factory->transformableType('Foo'));
        $this->assertTrue($this->factory->transformableType((object) ['foo' => 'bar']));
    }

    public function testTransformingResponse()
    {
        $this->factory->register('Dingo\Api\Tests\Stubs\UserStub', new UserTransformerStub);

        $response = $this->factory->transform(new UserStub);

        $this->assertEquals(['name' => 'Jason'], $response);
    }

    public function testTransformingCollectionResponse()
    {
        $this->factory->register('Dingo\Api\Tests\Stubs\UserStub', new UserTransformerStub);

        $response = $this->factory->transform(new Collection([new UserStub, new UserStub]));

        $this->assertEquals([['name' => 'Jason'], ['name' => 'Jason']], $response);
    }

    public function testTransformingResponseBoundByContract()
    {
        $response = $this->factory->transform(new UserContractStub);

        $this->assertEquals(['name' => 'Jason'], $response);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to find bound transformer for "Dingo\Api\Tests\Stubs\UserStub" class
     */
    public function testTransformingWithNoTransformerThrowsException()
    {
        $this->factory->transform(new UserStub);
    }
}
