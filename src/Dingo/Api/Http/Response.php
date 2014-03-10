<?php namespace Dingo\Api\Http;

use Illuminate\Support\MessageBag;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;

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
		// If a response is an indexed array consisting of two keys then it's assumed that
		// the first key is an error message and the second key is an instance of
		// \Illuminate\Support\MessageBag. These responses are treated as
		// unprocessable entities.
		if ($this->isUnprocessableEntity())
		{
			list ($message, $errors) = $this->original;

			$this->setStatusCode(422) and $this->original = compact('message', 'errors');
		}

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
			$this->content = $this->original->toJson();
		}
		elseif (is_string($this->original))
		{
			$this->content = json_encode(['message' => $this->original]);
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

			$this->content = json_encode($this->content);
		}

		return $this;
	}

	/**
	 * If content implements the ArrayableInterface it will be morphed to it's
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
	 * Determine if the response is an unprocessable entity.
	 * 
	 * @return bool
	 */
	protected function isUnprocessableEntity()
	{
		if (is_array($this->original) and count($this->original) == 2)
		{
			// Ensure that the array that was returned is in fact an indexed array and not
			// an associative array. We can do this by comparing the keys of the array
			// with the "keys of the keys". If they are not the same then we have an
			// associative array and must return false.
			$keys = array_keys($this->original);

			if ($keys !== array_keys($keys))
			{
				return false;
			}

			list ($message, $errors) = $this->original;

			return is_string($message) and $errors instanceof MessageBag;
		}
	}

}