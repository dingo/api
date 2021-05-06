<?php

namespace Dingo\Api\Tests\Http\Response;

use Closure;
use Dingo\Api\Http\Response\Factory;
use Dingo\Api\Tests\BaseTestCase;
use Dingo\Api\Tests\Stubs\UserStub;
use Dingo\Api\Transformer\Factory as TransformerFactory;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FactoryTest extends BaseTestCase
{
    /**
     * @var TransformerFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    protected $transformer;
    /**
     * @var Factory
     */
    protected $factory;

    public function setUp(): void
    {
        $this->transformer = Mockery::mock(TransformerFactory::class);
        $this->factory = new Factory($this->transformer);
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
        $this->assertNull($response->headers->get('Content-Type'));
    }

    public function testMakingCollectionRegistersUnderlyingClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with(UserStub::class, 'test', [], null);

        $this->assertInstanceOf(Collection::class, $this->factory->collection(new Collection([new UserStub('Jason')]), 'test')->getOriginalContent());
        $this->assertInstanceOf(Collection::class, $this->factory->withCollection(new Collection([new UserStub('Jason')]), 'test')->getOriginalContent());
    }

    public function testMakingCollectionResponseWithThreeParameters()
    {
        $this->transformer->shouldReceive('register')->twice()->with(UserStub::class, 'test', [], Mockery::on(function ($param) {
            return $param instanceof Closure;
        }));

        $this->assertInstanceOf(Collection::class, $this->factory->collection(new Collection([new UserStub('Jason')]), 'test', function ($resource, $fractal) {
            $this->assertInstanceOf(\League\Fractal\Resource\Collection::class, $resource);
            $this->assertInstanceOf(Manager::class, $fractal);
        })->getOriginalContent());
        $this->assertInstanceOf(Collection::class, $this->factory->withCollection(new Collection([new UserStub('Jason')]), 'test', function ($resource, $fractal) {
            $this->assertInstanceOf(\League\Fractal\Resource\Collection::class, $resource);
            $this->assertInstanceOf(Manager::class, $fractal);
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
        $this->transformer->shouldReceive('register')->twice()->with(UserStub::class, 'test', [], Mockery::on(function ($param) {
            return $param instanceof Closure;
        }));

        $this->assertInstanceOf(UserStub::class, $this->factory->item(new UserStub('Jason'), 'test', function ($resource, $fractal) {
            $this->assertInstanceOf(Item::class, $resource);
            $this->assertInstanceOf(Manager::class, $fractal);
        })->getOriginalContent());
        $this->assertInstanceOf(UserStub::class, $this->factory->withItem(new UserStub('Jason'), 'test', function ($resource, $fractal) {
            $this->assertInstanceOf(Item::class, $resource);
            $this->assertInstanceOf(Manager::class, $fractal);
        })->getOriginalContent());
    }

    public function testMakingPaginatorRegistersUnderlyingClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with(UserStub::class, 'test', [], null);

        $this->assertInstanceOf(Paginator::class, $this->factory->paginator(new Paginator([new UserStub('Jason')], 1), 'test')->getOriginalContent());
        $this->assertInstanceOf(Paginator::class, $this->factory->withPaginator(new Paginator([new UserStub('Jason')], 1), 'test')->getOriginalContent());
    }

    public function testNotFoundThrowsHttpException()
    {
        $this->expectException(HttpException::class);

        $this->factory->errorNotFound();
    }

    public function testBadRequestThrowsHttpException()
    {
        $this->expectException(HttpException::class);

        $this->factory->errorBadRequest();
    }

    public function testForbiddenThrowsHttpException()
    {
        $this->expectException(HttpException::class);

        $this->factory->errorForbidden();
    }

    public function testInternalThrowsHttpException()
    {
        $this->expectException(HttpException::class);

        $this->factory->errorInternal();
    }

    public function testUnauthorizedThrowsHttpException()
    {
        $this->expectException(HttpException::class);

        $this->factory->errorUnauthorized();
    }

    public function testMethodNotAllowedThrowsHttpException()
    {
        $this->expectException(HttpException::class);

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
