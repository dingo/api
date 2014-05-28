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
	 * Format other response type such as a string or integer.
	 * 
	 * @param  mixed  $content
	 * @return string
	 */
	public function formatOther($content);

	/**
	 * Format an array or instance implementing ArrayableInterface.
	 * 
	 * @param  array|\Illuminate\Support\Contracts\ArrayableInterface  $content
	 * @return string
	 */
	public function formatArray($content);

	/**
	 * Get the response content type.
	 * 
	 * @return string
	 */
	public function getContentType();
	
}
