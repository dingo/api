<?php

namespace Dingo\Api\Tests\Transformer;

use Mockery;
use PHPUnit\Framework\TestCase;
use Dingo\Api\Transformer\Factory;
use Illuminate\Support\Collection;
use Dingo\Api\Tests\Stubs\UserStub;
use Illuminate\Container\Container;
use Dingo\Api\Tests\Stubs\TransformerStub;
use Dingo\Api\Tests\Stubs\UserTransformerStub;

class FactoryTest extends TestCase
{
    protected $factory;

    public function setUp()
    {
        $container = new Container;
        $container['request'] = Mockery::mock(\Dingo\Api\Http\Request::class);

        $this->factory = new Factory($container, new TransformerStub);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testResponseIsTransformable()
    {
        $this->assertFalse($this->factory->transformableResponse(new UserStub('Jason'), new UserTransformerStub));

        $this->factory->register(\Dingo\Api\Tests\Stubs\UserStub::class, new UserTransformerStub);

        $this->assertTrue($this->factory->transformableResponse(new UserStub('Jason'), new UserTransformerStub));
    }

    public function testRegisterParameterOrder()
    {
        // Third parameter is parameters and fourth is callback.
        $binding = $this->factory->register(\Dingo\Api\Tests\Stubs\UserStub::class, new UserTransformerStub,
            ['foo' => 'bar'], function ($foo) {
                $this->assertSame('foo', $foo);
            });

        $binding->fireCallback('foo');
        $this->assertSame(['foo' => 'bar'], $binding->getParameters());

        // Third parameter is parameters and fourth is null.
        $binding = $this->factory->register(\Dingo\Api\Tests\Stubs\UserStub::class, new UserTransformerStub,
            ['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $binding->getParameters());

        // Third parameter is an empty array and fourth is callback.
        $binding = $this->factory->register(\Dingo\Api\Tests\Stubs\UserStub::class, new UserTransformerStub, [],
            function ($foo) {
                $this->assertSame('foo', $foo);
            });

        $binding->fireCallback('foo');
    }

    public function testResponseIsTransformableType()
    {
        $this->assertFalse($this->factory->transformableType(['foo' => 'bar']));
        $this->assertTrue($this->factory->transformableType('Foo'));
        $this->assertTrue($this->factory->transformableType((object) ['foo' => 'bar']));
    }

    public function testTransformingResponse()
    {
        $this->factory->register(\Dingo\Api\Tests\Stubs\UserStub::class, new UserTransformerStub);

        $response = $this->factory->transform(new UserStub('Jason'));

        $this->assertSame(['name' => 'Jason'], $response);
    }

    public function testTransformingCollectionResponse()
    {
        $this->factory->register(\Dingo\Api\Tests\Stubs\UserStub::class, new UserTransformerStub);

        $response = $this->factory->transform(new Collection([new UserStub('Jason'), new UserStub('Bob')]));

        $this->assertSame([['name' => 'Jason'], ['name' => 'Bob']], $response);
    }

    public function testTransforingWithIlluminateRequest()
    {
        $container = new Container;
        $container['request'] = new \Illuminate\Http\Request();

        $factory = new Factory($container, new TransformerStub);

        $factory->register(\Dingo\Api\Tests\Stubs\UserStub::class, new UserTransformerStub);

        $response = $factory->transform(new UserStub('Jason'));

        $this->assertSame(['name' => 'Jason'], $response);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to find bound transformer for "Dingo\Api\Tests\Stubs\UserStub" class
     */
    public function testTransformingWithNoTransformerThrowsException()
    {
        $this->factory->transform(new UserStub('Jason'));
    }
}
