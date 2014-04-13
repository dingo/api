<?php namespace Dingo\Api\Http\ResponseFormat;

interface ResponseFormatInterface {

	/**
	 * Format an Eloquent model.
	 * 
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @return string
	 */
	public function formatEloquentModel($model);

	/**
	 * Format an Eloquent collection.
	 * 
	 * @param  \Illuminate\Database\Eloquent\Collection  $collection
	 * @return string
	 */
	public function formatEloquentCollection($collection);

	/**
	 * Format a string.
	 * 
	 * @param  string  $string
	 * @return string
	 */
	public function formatString($string);

	/**
	 * Format an array or instance implementing ArrayableInterface.
	 * 
	 * @param  \Illuminate\Support\Contracts\ArrayableInterface  $response
	 * @return string
	 */
	public function formatArrayableInterface($response);

	/**
	 * Format an instance implementing JsonableInterface.
	 * 
	 * @param  \Illuminate\Support\Contracts\JsonableInterface  $response
	 * @return string
	 */
	public function formatJsonableInterface($response);

	/**
	 * Format an unknown type.
	 * 
	 * @param  mixed  $response
	 * @return string
	 */
	public function formatUnknown($response);

	/**
	 * Get the response content type.
	 * 
	 * @return string
	 */
	public function getContentType();
	
}