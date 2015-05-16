<?php

namespace Dingo\Api\Tests\Http\Response;

use Mockery;
use PHPUnit_Framework_TestCase;
use Illuminate\Support\Collection;
use Dingo\Api\Tests\Stubs\UserStub;
use Dingo\Api\Http\Response\Factory;
use Illuminate\Pagination\Paginator;

class FactoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->transformer = Mockery::mock('Dingo\Api\Transformer\Factory');
        $this->factory = new Factory($this->transformer);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testMakingACreatedResponse()
    {
        $response = $this->factory->created();
        $responseWithLocation = $this->factory->created('test');

        $this->assertEquals($response->getStatusCode(), 201);
        $this->assertFalse($response->headers->has('Location'));

        $this->assertEquals($responseWithLocation->getStatusCode(), 201);
        $this->assertTrue($responseWithLocation->headers->has('Location'));
        $this->assertEquals($responseWithLocation->headers->get('Location'), 'test');
    }

    public function testMakingANoContentResponse()
    {
        $response = $this->factory->noContent();
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
    }

    public function testMakingCollectionRegistersUnderlyingClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with('Dingo\Api\Tests\Stubs\UserStub', 'test', [], null);

        $this->factory->collection(new Collection([new UserStub]), 'test');
        $this->factory->withCollection(new Collection([new UserStub]), 'test');
    }

    public function testMakingItemsRegistersClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with('Dingo\Api\Tests\Stubs\UserStub', 'test', [], null);

        $this->factory->item(new UserStub, 'test');
        $this->factory->withItem(new UserStub, 'test');
    }

    public function testMakingPaginatorRegistersUnderlyingClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with('Dingo\Api\Tests\Stubs\UserStub', 'test', [], null);

        $this->factory->paginator(new Paginator([new UserStub], 1), 'test');
        $this->factory->withPaginator(new Paginator([new UserStub], 1), 'test');
    }

    public function testMakingErrorNotFoundResponse()
    {
        $response = $this->factory->errorNotFound();
        $this->assertEquals($response->getStatusCode(), 404);
        $this->assertEquals($response->getContent(), '{"status_code":404,"message":"Not Found"}');
    }

    public function testMakingBadRequestResponse()
    {
        $response = $this->factory->errorBadRequest();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"status_code":400,"message":"Bad Request"}', $response->getContent());
    }

    public function testMakingForbiddenResponse()
    {
        $response = $this->factory->errorForbidden();
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('{"status_code":403,"message":"Forbidden"}', $response->getContent());
    }

    public function testMakingInternalErrorResponse()
    {
        $response = $this->factory->errorInternal();
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('{"status_code":500,"message":"Internal Error"}', $response->getContent());
    }

    public function testMakingUnauthorizedErrorResponse()
    {
        $response = $this->factory->errorUnauthorized();
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('{"status_code":401,"message":"Unauthorized"}', $response->getContent());
    }

    public function testMakingArrayResponse()
    {
        $response = $this->factory->array(['foo' => 'bar']);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
    }

    public function testPrefixingWithCallsMethodsCorrectly()
    {
        $response = $this->factory->withArray(['foo' => 'bar']);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
    }
}
