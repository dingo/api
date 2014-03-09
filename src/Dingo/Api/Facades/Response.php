<?php namespace Dingo\Api\Facades;

use Dingo\Api\Http\Response as ApiResponse;
use Illuminate\Support\Facades\Response as IlluminateResponse;

class Response extends IlluminateResponse {

	/**
	 * Create and return a new API response.
	 * 
	 * @param  mixed  $content
	 * @param  int  $status
	 * @param  array  $headers
	 * @return \Dingo\Api\Http\Response
	 */
	public static function api($content, $status = 200, $headers = [])
	{
		return new ApiResponse($content, $status, $headers);
	}

}