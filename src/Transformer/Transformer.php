<?php namespace Dingo\Api\Transformer;

use Illuminate\Http\Request;

abstract class Transformer {

	/**
	 * Illuminate request instance.
	 * 
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * Set the illuminate request instance.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * Transform a response with a given transformer.
	 * 
	 * @param  string|object  $response
	 * @param  object  $transformer
	 * @return mixed
	 */
	abstract public function transformResponse($response, $transformer);

}
