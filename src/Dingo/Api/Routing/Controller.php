<?php namespace Dingo\Api\Routing;

use Dingo\Api\Dispatcher;

abstract class Controller extends \Illuminate\Routing\Controller {

	/**
	 * API dispatcher instance.
	 * 
	 * @var \Dingo\Api\Dispatcher
	 */
	protected $api;

	/**
	 * Array of unprotected controller methods.
	 * 
	 * @var array
	 */
	protected $unprotected = [];

	/**
	 * Array of protected controller methods.
	 * 
	 * @var array
	 */
	protected $protected = [];

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

	/**
	 * Unprotect controller methods.
	 * 
	 * @param  array  $methods
	 * @return void
	 */
	protected function unprotect($methods)
	{
		$this->unprotected = array_merge($this->unprotected, is_array($methods) ? $methods : func_get_args());
	}

	/**
	 * Protect controller methods.
	 * 
	 * @param  array  $methods
	 * @return void
	 */
	protected function protect($methods)
	{
		$this->protected = array_merge($this->protected, is_array($methods) ? $methods : func_get_args());
	}

	/**
	 * Get the protected controller methods.
	 * 
	 * @return array
	 */
	public function getProtectedMethods()
	{
		return $this->protected;
	}

	/**
	 * Get the unprotected controller methods.
	 * 
	 * @return array
	 */
	public function getUnprotectedMethods()
	{
		return $this->unprotected;
	}

}