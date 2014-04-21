<?php namespace Dingo\Api\Http;

use RuntimeException;
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
	 * Make an API response from an existing Illuminate response.
	 * 
	 * @param  \Illuminate\Http\Response  $response
	 * @return \Dingo\Api\Http\Response
	 */
	public static function makeFromExisting(\Illuminate\Http\Response $response)
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
		$formatter = static::getFormatter($format);

		$response = $this->original;

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

}
