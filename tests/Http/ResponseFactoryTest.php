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
        $this->transformer = Mockery::mock('Dingo\Api\Transformer\TransformerFactory');
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

    public function testMakingANoContentResponse()
    {
        $response = $this->factory->noContent()->build();
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

        $this->factory->paginator(new Paginator(Mockery::mock('Illuminate\Pagination\Factory'), [new UserStub], 1), 'test');
        $this->factory->withPaginator(new Paginator(Mockery::mock('Illuminate\Pagination\Factory'), [new UserStub], 1), 'test');
    }

    public function testMakingErrorNotFoundResponse()
    {
        $response = $this->factory->errorNotFound()->build();
        $this->assertEquals($response->getStatusCode(), 404);
        $this->assertEquals($response->getContent(), '{"status_code":404,"message":"Not Found"}');
    }

    public function testMakingBadRequestResponse()
    {
        $response = $this->factory->errorBadRequest()->build();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"status_code":400,"message":"Bad Request"}', $response->getContent());
    }

    public function testMakingForbiddenResponse()
    {
        $response = $this->factory->errorForbidden()->build();
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('{"status_code":403,"message":"Forbidden"}', $response->getContent());
    }

    public function testMakingInternalErrorResponse()
    {
        $response = $this->factory->errorInternal()->build();
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('{"status_code":500,"message":"Internal Error"}', $response->getContent());
    }

    public function testMakingUnauthorizedErrorResponse()
    {
        $response = $this->factory->errorUnauthorized()->build();
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('{"status_code":401,"message":"Unauthorized"}', $response->getContent());
    }

    public function testMakingArrayResponse()
    {
        $response = $this->factory->array(['foo' => 'bar'])->build();
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
    }

    public function testPrefixingWithCallsMethodsCorrectly()
    {
        $response = $this->factory->withArray(['foo' => 'bar'])->build();
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
    }
}
