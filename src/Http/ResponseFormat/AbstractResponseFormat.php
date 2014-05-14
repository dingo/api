<?php namespace Dingo\Api\Http\ResponseFormat;

abstract class AbstractResponseFormat {

	/**
	 * Format an Eloquent model.
	 *
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @return string
	 */
	abstract public function formatEloquentModel($model);

	/**
	 * Format an Eloquent collection.
	 *
	 * @param  \Illuminate\Database\Eloquent\Collection  $collection
	 * @return string
	 */
	abstract public function formatEloquentCollection($collection);

	/**
	 * Format a string.
	 *
	 * @param  string  $string
	 * @return string
	 */
	abstract public function formatString($string);

	/**
	 * Format an array or instance implementing ArrayableInterface.
	 *
	 * @param  \Illuminate\Support\Contracts\ArrayableInterface  $response
	 * @return string
	 */
	abstract public function formatArrayableInterface($response);

	/**
	 * Format an instance implementing JsonableInterface.
	 *
	 * @param  \Illuminate\Support\Contracts\JsonableInterface  $response
	 * @return string
	 */
	abstract public function formatJsonableInterface($response);

	/**
	 * Format an unknown type.
	 *
	 * @param  mixed  $response
	 * @return string
	 */
	abstract public function formatUnknown($response);

	/**
	 * Get the response content type.
	 *
	 * @return string
	 */
	abstract public function getContentType();

	/**
	 * Set the response content type.
	 *
	 * @return string
	 */
	abstract public function setRequest($request);

}