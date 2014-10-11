<?php

namespace Dingo\Api\Tests\Transformer;

use PHPUnit_Framework_TestCase;
use Illuminate\Support\Collection;
use Dingo\Api\Tests\Stubs\UserStub;
use Dingo\Api\Tests\Stubs\TransformerStub;
use Dingo\Api\Tests\Stubs\UserContractStub;
use Dingo\Api\Tests\Stubs\UserTransformerStub;

class TransformerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->transformer = new TransformerStub;
    }


    public function testResponseIsTransformable()
    {
        $this->assertFalse($this->transformer->transformableResponse(new UserStub, new UserTransformerStub));

        $this->transformer->register('Dingo\Api\Tests\Stubs\UserStub', new UserTransformerStub);

        $this->assertTrue($this->transformer->transformableResponse(new UserStub, new UserTransformerStub));
    }


    public function testResponseIsTransformableType()
    {
        $this->assertFalse($this->transformer->transformableType(['foo' => 'bar']));
        $this->assertTrue($this->transformer->transformableType('Foo'));
        $this->assertTrue($this->transformer->transformableType((object) ['foo' => 'bar']));
    }


    public function testTransformingResponse()
    {
        $this->transformer->register('Dingo\Api\Tests\Stubs\UserStub', new UserTransformerStub);

        $response = $this->transformer->transform(new UserStub);

        $this->assertEquals(['name' => 'Jason'], $response);
    }


    public function testTransformingCollectionResponse()
    {
        $this->transformer->register('Dingo\Api\Tests\Stubs\UserStub', new UserTransformerStub);

        $response = $this->transformer->transform(new Collection([new UserStub, new UserStub]));

        $this->assertEquals([['name' => 'Jason'], ['name' => 'Jason']], $response);
    }


    public function testTransformingResponseBoundByContract()
    {
        $response = $this->transformer->transform(new UserContractStub);

        $this->assertEquals(['name' => 'Jason'], $response);
    }


    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to find bound transformer for "Dingo\Api\Tests\Stubs\UserStub" class
     */
    public function testTransformingWithNoTransformerThrowsException()
    {
        $this->transformer->transform(new UserStub);
    }
}
