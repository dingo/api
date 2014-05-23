<?php namespace Dingo\Api\Console;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Router;
use Illuminate\Routing\Route;
use Illuminate\Foundation\Console\RoutesCommand;
use Symfony\Component\Console\Input\InputOption;

class ApiRoutesCommand extends RoutesCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'api:routes';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'List all registered API routes';

	/**
	 * The table headers for the command.
	 *
	 * @var array
	 */
	protected $headers = ['Domain', 'URI', 'Name', 'Action', 'Version(s)', 'Protected', 'Scope(s)'];

	/**
	 * Create a new route command instance.
	 *
	 * @param  \Illuminate\Routing\Router  $router
	 * @return void
	 */
	public function __construct(Router $router)
	{
		parent::__construct($router);

		$this->routes = $router->getApiRoutes();
	}

	/**
	 * Compile the routes into a displayable format.
	 *
	 * @return array
	 */
	protected function getRoutes()
	{
		$results = [];

		foreach ($this->routes as $collection)
		{
			foreach ($collection->getRoutes() as $route)
			{
				$results[] = $this->getRouteInformation($route);
			}
		}

		$results = array_unique($results, SORT_REGULAR);

		return array_filter($results);
	}

	/**
	 * Get the route information for a given route.
	 *
	 * @param  string $name
	 * @param  \Illuminate\Routing\Route $route
	 *
	 * @return array
	 */
	protected function getRouteInformation(Route $route)
	{
		return $this->filterRoute(array(
			'host'      => $route->domain(),
			'uri'       => implode('|', $route->methods()).' '.$route->uri(),
			'name'      => $route->getName(),
			'action'    => $route->getActionName(),
			'version'   => implode(', ', array_get($route->getAction(), 'version')),
			'protected' => array_get($route->getAction(), 'protected') ? 'Yes' : 'No',
			'scopes'    => $this->getScopes($route)
		));
	}

	/**
	 * Get the scopes of a route.
	 *
	 * @param  \Illuminate\Routing\Route $route
	 * @return string
	 */
	protected function getScopes($route)
	{
		$scopes = array_get($route->getAction(), 'scopes');
		
		return is_array($scopes) ? implode(', ', $scopes) : $scopes;
	}

}
