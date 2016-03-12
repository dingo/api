<?php

namespace Dingo\Api\Console\Command;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Dingo\Api\Routing\Router;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class RoutesLumen extends Command
{
    /**
     * Array of route collections.
     *
     * @var array
     */
    protected $routes;

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
    protected $description = 'List all registeted API routes';

    /**
     * The table headers for the command.
     *
     * @var array
     */
    protected $headers = ['Host', 'URI', 'Name', 'Action', 'Protected', 'Version(s)', 'Scope(s)', 'Rate Limit'];

    /**
     * Create a new routes command instance.
     *
     * @param \Dingo\Api\Routing\Router $router
     *
     * @return void
     */
    public function __construct(Router $router)
    {
        parent::__construct();
        $this->routes = $router->getRoutes();
    }
    
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (count($this->routes) == 0) {
            return $this->error("Your application doesn't have any routes.");
        }
        $this->table($this->headers, $this->getRoutes());
    }

    /**
     * Compile the routes into a displayable format.
     *
     * @return array
     */
    protected function getRoutes()
    {
        $routes = [];

        foreach ($this->routes as $collection) {
            foreach ($collection->getRoutes() as $route) {
                $routes[] = $this->filterRoute([
                    'host'      => $route->domain(),
                    'uri'       => implode('|', $route->methods()).' '.$route->uri(),
                    'name'      => $route->getName(),
                    'action'    => $route->getActionName(),
                    'protected' => $route->isProtected() ? 'Yes' : 'No',
                    'versions'  => implode(', ', $route->versions()),
                    'scopes'    => implode(', ', $route->scopes()),
                    'rate'      => $this->routeRateLimit($route),
                ]);
            }
        }

        if ($sort = $this->option('sort')) {
            $routes = Arr::sort($routes, function ($value) use ($sort) {
                return $value[$sort];
            });
        }

        if ($this->option('reverse')) {
            $routes = array_reverse($routes);
        }

        return array_filter(array_unique($routes, SORT_REGULAR));
    }

    /**
     * Display the routes rate limiting requests per second. This takes the limit
     * and divides it by the expiration time in seconds to give you a rough
     * idea of how many requests you'd be able to fire off per second
     * on the route.
     *
     * @param \Dingo\Api\Routing\Route $route
     *
     * @return null|string
     */
    protected function routeRateLimit($route)
    {
        list($limit, $expires) = [$route->getRateLimit(), $route->getRateLimitExpiration()];

        if ($limit && $expires) {
            return sprintf('%s req/s', round($limit / ($expires * 60), 2));
        }
    }

    /**
     * Filter the route by URI, Version, Scopes and / or name.
     *
     * @param array $route
     *
     * @return array|null
     */
    protected function filterRoute(array $route)
    {
        $filters = ['name', 'path', 'protected', 'unprotected', 'versions', 'scopes'];

        foreach ($filters as $filter) {
            if ($this->option($filter) && ! $this->{'filterBy'.ucfirst($filter)}($route)) {
                return;
            }
        }

        return $route;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        //$options from Laravel route:list
        $options = [
            ['method', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by method.'],
            ['name', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by name.'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by path.'],
            ['reverse', 'r', InputOption::VALUE_NONE, 'Reverse the ordering of the routes.'],
            ['sort', null, InputOption::VALUE_OPTIONAL, 'The column (host, method, uri, name, action, middleware) to sort by.', 'uri'],
        ];

        foreach ($options as $key => $option) {
            if ($option[0] == 'sort') {
                unset($options[$key]);
            }
        }

        return array_merge(
            $options,
            [
                ['sort', null, InputOption::VALUE_OPTIONAL, 'The column (domain, method, uri, name, action) to sort by'],
                ['versions', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Filter the routes by version'],
                ['scopes', 'S', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Filter the routes by scopes'],
                ['protected', null, InputOption::VALUE_NONE, 'Filter the protected routes'],
                ['unprotected', null, InputOption::VALUE_NONE, 'Filter the unprotected routes'],
            ]
        );
    }

    /**
     * Filter the route by its path.
     *
     * @param array $route
     *
     * @return bool
     */
    protected function filterByPath(array $route)
    {
        return Str::contains($route['uri'], $this->option('path'));
    }

    /**
     * Filter the route by whether or not it is protected.
     *
     * @param array $route
     *
     * @return bool
     */
    protected function filterByProtected(array $route)
    {
        return $this->option('protected') && $route['protected'] == 'Yes';
    }

    /**
     * Filter the route by whether or not it is unprotected.
     *
     * @param array $route
     *
     * @return bool
     */
    protected function filterByUnprotected(array $route)
    {
        return $this->option('unprotected') && $route['protected'] == 'No';
    }

    /**
     * Filter the route by its versions.
     *
     * @param array $route
     *
     * @return bool
     */
    protected function filterByVersions(array $route)
    {
        foreach ($this->option('versions') as $version) {
            if (Str::contains($route['versions'], $version)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter the route by its name.
     *
     * @param array $route
     *
     * @return bool
     */
    protected function filterByName(array $route)
    {
        return Str::contains($route['name'], $this->option('name'));
    }

    /**
     * Filter the route by its scopes.
     *
     * @param array $route
     *
     * @return bool
     */
    protected function filterByScopes(array $route)
    {
        foreach ($this->option('scopes') as $scope) {
            if (Str::contains($route['scopes'], $scope)) {
                return true;
            }
        }

        return false;
    }

}
