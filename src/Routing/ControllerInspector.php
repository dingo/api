<?php namespace Dingo\Api\Routing;

use ReflectionMethod;

class ControllerInspector extends \Illuminate\Routing\ControllerInspector {

	/**
	 * Determine if the given controller method is routable.
	 *
	 * @param  ReflectionMethod  $method
	 * @param  string  $controller
	 * @return bool
	 */
	public function isRoutable(ReflectionMethod $method, $controller)
	{
		if ($method->class == 'Dingo\Api\Routing\Controller') return false;

		return parent::isRoutable($method, $controller);
	}

}