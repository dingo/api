<?php namespace Dingo\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

abstract class Provider {

	/**
	 * Array of provider specific options.
	 * 
	 * @var array
	 */
	protected $options = [];

	/**
	 * Set the provider specific options.
	 * 
	 * @param  array  $options
	 * @return \Dingo\Api\Auth\Provider
	 */
	public function setOptions(array $options)
	{
		$this->options = array_merge($this->options, $options);

		return $this;
	}

	/**
	 * Authenticate the request and return the authenticated users ID.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Illuminate\Routing\Route  $route
	 * @return int
	 */
	abstract public function authenticate(Request $request, Route $route);

}
