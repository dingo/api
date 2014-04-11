<?php namespace Dingo\Api\Http;

use Illuminate\Support\MessageBag;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class Response extends \Illuminate\Http\Response {

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
	public function morph()
	{
		$this->headers->set('content-type', 'application/json');

		return $this->morphJsonResponse();
	}

	/**
	 * Morph the response to it's JSON representation by checking the type
	 * of response that's going to be returned.
	 * 
	 * @return \Dingo\Api\Http\Response
	 */
	protected function morphJsonResponse()
	{
		if ($this->original instanceof JsonableInterface)
		{
			$this->content = $this->morphJsonableInterface($this->original);
		}
		elseif (is_string($this->original))
		{
			$this->content = $this->encode(['message' => $this->original]);
		}
		else
		{
			$this->content = $this->morphArrayableInterface($this->original);

			if (is_array($this->content))
			{
				array_walk_recursive($this->content, function(&$value)
				{
					$value = $this->morphArrayableInterface($value);
				});
			}

			$this->content = $this->encode($this->content);
		}

		return $this;
	}

	/**
	 * If content implements the ArrayableInterface it will be morphed to its
	 * array value.
	 * 
	 * @param  array|\Illuminate\Support\Contracts\ArrayableInterface  $content
	 * @return array
	 */
	protected function morphArrayableInterface($content)
	{
		return $content instanceof ArrayableInterface ? $content->toArray() : $content;
	}

	/**
	 * If content implements the JsonableInterface it will be morphed to its
	 * JSON value.
	 * 
	 * @param  \Illuminate\Support\Contracts\JsonableInterface  $content
	 * @return string
	 */
	protected function morphJsonableInterface($content)
	{
		if ($content instanceof EloquentModel)
		{
			$key = $content->getTable();

			return $this->encode([$key => $content->toArray()]);
		}
		elseif ($content instanceof EloquentCollection)
		{
			$key = str_plural($content->first()->getTable());

			return $this->encode([$key => $content->toArray()]);
		}
		else
		{
			return $content->toJson();
		}
	}

	/**
	 * Encode the content to its JSON representation.
	 * 
	 * @param  string  $content
	 * @return string
	 */
	protected function encode($content)
	{
		return json_encode($content);
	}

}