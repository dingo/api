<?php namespace Dingo\Api\Http\ResponseFormat;

interface RequestAwareInterface {

	/**
	 * Set the Request
	 *
	 * @param \Illuminate\Http\Request $request;
	 */
	public function setRequest($request);

}
