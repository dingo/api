<?php namespace Dingo\Api\Routing;

use Dingo\Api\Api;
use Illuminate\Routing\Controller as IlluminateController;

class Controller extends IlluminateController {

	/**
	 * API dispatcher instance.
	 * 
	 * @var \Dingo\Api\Api
	 */
	protected $api;

	/**
	 * Create a new controller instance.
	 * 
	 * @param  \Dingo\Api\Api  $api
	 * @return void
	 */
	public function __construct(Api $api)
	{
		$this->api = $api;
	}

}