<?php namespace Dingo\Api\Routing;

use Dingo\Api\Dispatcher;
use Illuminate\Routing\Controller as IlluminateController;

class Controller extends IlluminateController {

	/**
	 * API dispatcher instance.
	 * 
	 * @var \Dingo\Api\Dispatcher
	 */
	protected $api;

	/**
	 * Create a new controller instance.
	 * 
	 * @param  \Dingo\Api\Dispatcher  $api
	 * @return void
	 */
	public function __construct(Dispatcher $api)
	{
		$this->api = $api;
	}

}