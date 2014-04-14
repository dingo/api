<?php namespace Dingo\Api\Http\ResponseFormat;

use Illuminate\Support\Contracts\ArrayableInterface;

class JsonResponseFormat implements ResponseFormatInterface {

	/**
	 * Format an Eloquent model.
	 * 
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @return string
	 */
	public function formatEloquentModel($model)
	{
		$key = str_singular($model->getTable());

		return $this->encode([$key => $model->toArray()]);
	}

	/**
	 * Format an Eloquent collection.
	 * 
	 * @param  \Illuminate\Database\Eloquent\Collection  $collection
	 * @return string
	 */
	public function formatEloquentCollection($collection)
	{
		if ($collection->isEmpty())
		{
			return $this->encode([]);
		}

		$key = str_plural($collection->first()->getTable());

		return $this->encode([$key => $collection->toArray()]);
	}

	/**
	 * Format a string.
	 * 
	 * @param  string  $string
	 * @return string
	 */
	public function formatString($string)
	{
		return $this->encode(['message' => $string]);
	}

	/**
	 * Format an array or instance implementing ArrayableInterface.
	 * 
	 * @param  \Illuminate\Support\Contracts\ArrayableInterface  $response
	 * @return string
	 */
	public function formatArrayableInterface($response)
	{
		$response = $this->morphToArray($response);

		array_walk_recursive($response, function(&$value)
		{
			$value = $this->morphToArray($value);
		});

		return $this->encode($response);
	}

	/**
	 * Format an instance implementing JsonableInterface.
	 * 
	 * @param  \Illuminate\Support\Contracts\JsonableInterface  $response
	 * @return string
	 */
	public function formatJsonableInterface($response)
	{
		return $response->toJson();
	}

	/**
	 * Format an unknown type.
	 * 
	 * @param  mixed  $response
	 * @return string
	 */
	public function formatUnknown($response)
	{
		return $this->encode($response);
	}

	/**
	 * Get the response content type.
	 * 
	 * @return string
	 */
	public function getContentType()
	{
		return 'application/json';
	}

	/**
	 * Morph a value to an array.
	 * 
	 * @param  array|\Illuminate\Support\Contracts\ArrayableInterface
	 * @return array
	 */
	protected function morphToArray($value)
	{
		return $value instanceof ArrayableInterface ? $value->toArray() : $value;
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