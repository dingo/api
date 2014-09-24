<?php

namespace Dingo\Api\Tests\Http;

use Mockery;
use PHPUnit_Framework_TestCase;
use Illuminate\Support\Collection;
use Dingo\Api\Http\ResponseFactory;
use Dingo\Api\Tests\Stubs\UserStub;
use Illuminate\Pagination\Paginator;

class ResponseFactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->transformer = Mockery::mock('Dingo\Api\Transformer\Transformer');
        $this->factory = new ResponseFactory($this->transformer);
    }


    public function tearDown()
    {
        Mockery::close();
    }

    public function testMakingACreatedResponse()
    {
        $response = $this->factory->created()->build();
        $responseWithLocation = $this->factory->created('test')->build();

        $this->assertEquals($response->getStatusCode(), 201);
        $this->assertFalse($response->headers->has('Location'));

        $this->assertEquals($responseWithLocation->getStatusCode(), 201);
        $this->assertTrue($responseWithLocation->headers->has('Location'));
        $this->assertEquals($responseWithLocation->headers->get('Location'), 'test');
    }


    public function testMakingCollectionRegistersUnderlyingClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with('Dingo\Api\Tests\Stubs\UserStub', 'test', [], null);

        $this->factory->collection(new Collection([new UserStub]), 'test');
        $this->factory->withCollection(new Collection([new UserStub]), 'test');
    }


    public function testMakingItemsRegistersClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with('Dingo\Api\Tests\Stubs\UserStub', 'test', []);

        $this->factory->item(new UserStub, 'test');
        $this->factory->withItem(new UserStub, 'test');
    }


    public function testMakingPaginatorRegistersUnderlyingClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with('Dingo\Api\Tests\Stubs\UserStub', 'test', [], null);

        $this->factory->paginator(new Paginator(Mockery::mock('Illuminate\Pagination\Factory'), [new UserStub], 1), 'test');
        $this->factory->withPaginator(new Paginator(Mockery::mock('Illuminate\Pagination\Factory'), [new UserStub], 1), 'test');
    }
}
