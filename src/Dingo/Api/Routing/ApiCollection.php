<?php namespace Dingo\Api\Routing;

use Illuminate\Routing\RouteCollection;

class ApiCollection extends RouteCollection {

	/**
	 * API version.
	 * 
	 * @var string
	 */
	protected $version;

	/**
	 * API options.
	 * 
	 * @var array
	 */
	protected $options;

	/**
	 * Create a new dispatcher instance.
	 * 
	 * @param  string  $version
	 * @param  array  $options
	 * @return void
	 */
	public function __construct($version, array $options)
	{
		$this->version = $version;
		$this->options = $options;
	}

	/**
	 * Get an option from the collection.
	 * 
	 * @param  string  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function option($key, $default = null)
	{
		return array_get($this->options, $key, $default);
	}

	/**
	 * Determine if the routes within the collection will be a match for
	 * the current request.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return bool
	 */
	public function matches($request)
	{
		if ($this->option('domain') and $request->header('host') == $this->option('domain'))
		{
			return true;
		}
		elseif ($this->option('prefix') and starts_with($request->getPathInfo(), "/{$this->option('prefix')}"))
		{
			return true;
		}
		elseif ( ! $this->option('prefix') and ! $this->option('domain'))
		{
			return true;
		}

		return false;
	}

}