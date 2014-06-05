<?php namespace Dingo\Api\Http;

use Illuminate\Container\Container;
use League\Fractal\Manager as Fractal;
use League\Fractal\Resource\Item as FractalItem;
use Illuminate\Http\Response as IlluminateResponse;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Pagination\PaginatorInterface as FractalPaginatorInterface;

class ResponseBuilder {

	/**
	 * Illuminate container instance.
	 * 
	 * @var \Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * The HTTP response status code.
	 * 
	 * @var int
	 */
	protected $statusCode = 200;

	/**
	 * Create a new response builder instance.
	 * 
	 * @param  \Illuminate\Container\Container  $container
	 * @return void
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Create a new collection resource with the given tranformer.
	 * 
	 * @param  array|object  $collection
	 * @param  object  $transformer
	 * @param  array  $headers
	 * @return \Illuminate\Http\Response
	 */
	public function withCollection($collection, $transformer, array $headers = [])
	{
		$resource = new FractalCollection($collection, $transformer);

		return new IlluminateResponse($this->resolveFractal()->createData($resource)->toArray(), $this->statusCode, $headers);
	}

	/**
	 * Create a new item resource with the given transformer.
	 * 
	 * @param  array|object  $item
	 * @param  object  $transformer
	 * @param  array  $headers
	 * @return \Illuminate\Http\Response
	 */
	public function withItem($item, $transformer, array $headers = [])
	{
		$resource = new FractalItem($item, $transformer);

		return new IlluminateResponse($this->resolveFractal()->createData($resource)->toArray(), $this->statusCode, $headers);
	}

	/**
	 * Create a new collection resource from a paginator with the given transformer.
	 * 
	 * @param  \League\Fractal\Pagination\PaginatorInterface  $paginator
	 * @param  object  $transformer
	 * @param  array  $headers
	 * @return \Illuminate\Http\Response
	 */
	public function withPaginator(FractalPaginatorInterface $paginator, $transformer, array $headers = [])
	{
		$resource = new FractalCollection($paginator->getCollection(), $transformer);

		$resource->setPaginator($paginator);

		return new IlluminateResponse($this->resolveFractal()->createData($resource)->toArray(), $this->statusCode, $headers);
	}

	/**
	 * Return an array response.
	 * 
	 * @param  array  $array
	 * @param  array  $headers
	 * @return \Illuminate\Http\Response
	 */
	public function withArray(array $array, array $headers = [])
	{
		return new IlluminateResponse($array, $this->statusCode, $headers);
	}

	/**
	 * Set the responses status code.
	 * 
	 * @param  int  $statusCode
	 * @return \Dingo\Api\Http\ResponseBuilder
	 */
	public function setStatusCode($statusCode)
	{
		$this->statusCode = $statusCode;

		return $this;
	}

	/**
	 * Resolve the Fractal manager.
	 * 
	 * @return void
	 */
	protected function resolveFractal()
	{
		$transformer = $this->container->make('dingo.api.transformer');

		$transformer->parseFractalIncludes();

		return $transformer->getFractal();
	}

}
