<?php namespace Dingo\Api\Http;

use RuntimeException;
use Dingo\Api\Transformer\Factory;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class Response extends IlluminateResponse {

	/**
	 * Array of registered formatters.
	 * 
	 * @var array
	 */
	protected static $formatters = [];

	/**
	 * Transformer factory instance.
	 * 
	 * @var \Dingo\Api\Transformer\Factory
	 */
	protected static $transformer;

	/**
	 * Make an API response from an existing Illuminate response.
	 * 
	 * @param  \Illuminate\Http\Response  $response
	 * @return \Dingo\Api\Http\Response
	 */
	public static function makeFromExisting(IlluminateResponse $response)
	{
		return new static($response->getOriginalContent(), $response->getStatusCode(), $response->headers->all());
	}

	/**
	 * Process the API response.
	 * 
	 * @return \Dingo\Api\Http\Response
	 */
	public function morph($format = 'json')
	{
		$response = $this->getOriginalContent();

		if (static::$transformer->transformableResponse($response))
		{
			$response = static::$transformer->transformResponse($response);
		}

		$formatter = static::getFormatter($format);

		// First we'll attempt to format the response if it's either an Eloquent
		// model or an Eloquent collection.
		if ($response instanceof EloquentModel)
		{
			$response = $formatter->formatEloquentModel($response);
		}
		elseif ($response instanceof EloquentCollection)
		{
			$response = $formatter->formatEloquentCollection($response);
		}
		else
		{
			// Next we'll attempt to format the response if it's a string,
			// an array or an object implementing ArrayableInterface,
			// an object implementing JsonableInterface or an
			// unknown type.
			if (is_string($response))
			{
				$response = $formatter->formatString($response);
			}
			elseif (is_array($response) or $response instanceof ArrayableInterface)
			{
				$response = $formatter->formatArrayableInterface($response);
			}
			elseif ($response instanceof JsonableInterface)
			{
				$response = $formatter->formatJsonableInterface($response);
			}
			else
			{
				$response = $formatter->formatUnknown($response);
			}
		}

		// Set the "Content-Type" header of the response to that which
		// is defined by the formatter being used.
		$this->headers->set('content-type', $formatter->getContentType());

		// Directly set the property because using setContent results in
		// the original content also being updated.
		$this->content = $response;

		return $this;
	}

	/**
	 * Get the formatter based on the requested format type.
	 * 
	 * @param  string  $format
	 * @return \Dingo\Api\Http\Format\FormatInterface
	 * @throws \RuntimeException
	 */
	public static function getFormatter($format)
	{
		if ( ! isset(static::$formatters[$format]))
		{
			throw new RuntimeException('Response formatter "'.$format.'" has not been registered.');
		}

		return static::$formatters[$format];
	}

	/**
	 * Set the response formatters.
	 * 
	 * @param  array  $formatters
	 * @return void
	 */
	public static function setFormatters(array $formatters)
	{
		static::$formatters = $formatters;
	}

	/**
	 * Set the transformer factory instance.
	 * 
	 * @param  \Dingo\Api\Transformer\Factory  $transformer
	 * @return void
	 */
	public static function setTransformer(Factory $transformer)
	{
		static::$transformer = $transformer;
	}

	/**
	 * Get the transformer factory instance.
	 * 
	 * @return \Dingo\Api\Transformer\Factory
	 */
	public static function getTransformer()
	{
		return static::$transformer;
	}

}
