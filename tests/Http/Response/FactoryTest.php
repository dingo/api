<?php

namespace Dingo\Api\Tests\Http\Response;

use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;
use Dingo\Api\Tests\Stubs\UserStub;
use Dingo\Api\Http\Response\Factory;
use Illuminate\Pagination\Paginator;
use Dingo\Api\Transformer\Factory as TransformerFactory;

class FactoryTest extends TestCase
{
    protected $transformer;
    protected $factory;

    public function setUp()
    {
        $this->transformer = Mockery::mock(TransformerFactory::class);
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

        $this->assertSame($response->getStatusCode(), 201);
        $this->assertFalse($response->headers->has('Location'));

        $this->assertSame($responseWithLocation->getStatusCode(), 201);
        $this->assertTrue($responseWithLocation->headers->has('Location'));
        $this->assertSame($responseWithLocation->headers->get('Location'), 'test');
    }

    public function testMakingAnAcceptedResponse()
    {
        $response = $this->factory->accepted();
        $responseWithLocation = $this->factory->accepted('testHeader');
        $responseWithContent = $this->factory->accepted(null, 'testContent');
        $responseWithBoth = $this->factory->accepted('testHeader', 'testContent');

        $this->assertSame($response->getStatusCode(), 202);
        $this->assertFalse($response->headers->has('Location'));
        $this->assertSame('', $response->getContent());

        $this->assertSame($responseWithLocation->getStatusCode(), 202);
        $this->assertTrue($responseWithLocation->headers->has('Location'));
        $this->assertSame($responseWithLocation->headers->get('Location'), 'testHeader');
        $this->assertSame('', $responseWithLocation->getContent());

        $this->assertSame($responseWithContent->getStatusCode(), 202);
        $this->assertFalse($responseWithContent->headers->has('Location'));
        $this->assertSame('testContent', $responseWithContent->getContent());

        $this->assertSame($responseWithBoth->getStatusCode(), 202);
        $this->assertTrue($responseWithBoth->headers->has('Location'));
        $this->assertSame($responseWithBoth->headers->get('Location'), 'testHeader');
        $this->assertSame('testContent', $responseWithBoth->getContent());
    }

    public function testMakingANoContentResponse()
    {
        $response = $this->factory->noContent();
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testMakingCollectionRegistersUnderlyingClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with(UserStub::class, 'test', [], null);

        $this->assertInstanceOf(Collection::class, $this->factory->collection(new Collection([new UserStub('Jason')]), 'test')->getOriginalContent());
        $this->assertInstanceOf(Collection::class, $this->factory->withCollection(new Collection([new UserStub('Jason')]), 'test')->getOriginalContent());
    }

    public function testMakingCollectionResponseWithThreeParameters()
    {
        $this->transformer->shouldReceive('register')->twice()->with(\Dingo\Api\Tests\Stubs\UserStub::class, 'test', [], Mockery::on(function ($param) {
            return $param instanceof \Closure;
        }));

        $this->assertInstanceOf(Collection::class, $this->factory->collection(new Collection([new UserStub('Jason')]), 'test', function ($resource, $fractal) {
            $this->assertInstanceOf(\League\Fractal\Resource\Collection::class, $resource);
            $this->assertInstanceOf(\League\Fractal\Manager::class, $fractal);
        })->getOriginalContent());
        $this->assertInstanceOf(Collection::class, $this->factory->withCollection(new Collection([new UserStub('Jason')]), 'test', function ($resource, $fractal) {
            $this->assertInstanceOf(\League\Fractal\Resource\Collection::class, $resource);
            $this->assertInstanceOf(\League\Fractal\Manager::class, $fractal);
        })->getOriginalContent());
    }

    public function testMakingItemsRegistersClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with(UserStub::class, 'test', [], null);

        $this->assertInstanceOf(UserStub::class, $this->factory->item(new UserStub('Jason'), 'test')->getOriginalContent());
        $this->assertInstanceOf(UserStub::class, $this->factory->withItem(new UserStub('Jason'), 'test')->getOriginalContent());
    }

    public function testMakingItemResponseWithThreeParameters()
    {
        $this->transformer->shouldReceive('register')->twice()->with(\Dingo\Api\Tests\Stubs\UserStub::class, 'test', [], Mockery::on(function ($param) {
            return $param instanceof \Closure;
        }));

        $this->assertInstanceOf(\Dingo\Api\Tests\Stubs\UserStub::class, $this->factory->item(new UserStub('Jason'), 'test', function ($resource, $fractal) {
            $this->assertInstanceOf(\League\Fractal\Resource\Item::class, $resource);
            $this->assertInstanceOf(\League\Fractal\Manager::class, $fractal);
        })->getOriginalContent());
        $this->assertInstanceOf(\Dingo\Api\Tests\Stubs\UserStub::class, $this->factory->withItem(new UserStub('Jason'), 'test', function ($resource, $fractal) {
            $this->assertInstanceOf(\League\Fractal\Resource\Item::class, $resource);
            $this->assertInstanceOf(\League\Fractal\Manager::class, $fractal);
        })->getOriginalContent());
    }

    public function testMakingPaginatorRegistersUnderlyingClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with(UserStub::class, 'test', [], null);

        $this->assertInstanceOf(Paginator::class, $this->factory->paginator(new Paginator([new UserStub('Jason')], 1), 'test')->getOriginalContent());
        $this->assertInstanceOf(Paginator::class, $this->factory->withPaginator(new Paginator([new UserStub('Jason')], 1), 'test')->getOriginalContent());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testNotFoundThrowsHttpException()
    {
        $this->factory->errorNotFound();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testBadRequestThrowsHttpException()
    {
        $this->factory->errorBadRequest();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testForbiddenThrowsHttpException()
    {
        $this->factory->errorForbidden();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testInternalThrowsHttpException()
    {
        $this->factory->errorInternal();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testUnauthorizedThrowsHttpException()
    {
        $this->factory->errorUnauthorized();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testMethodNotAllowedThrowsHttpException()
    {
        $this->factory->errorMethodNotAllowed();
    }

    public function testMakingArrayResponse()
    {
        $response = $this->factory->array(['foo' => 'bar']);
        $this->assertSame('{"foo":"bar"}', $response->getContent());
    }

    public function testPrefixingWithCallsMethodsCorrectly()
    {
        $response = $this->factory->withArray(['foo' => 'bar']);
        $this->assertSame('{"foo":"bar"}', $response->getContent());
    }
}
