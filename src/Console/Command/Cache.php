<?php

namespace Dingo\Api\Console\Command;

use Dingo\Api\Routing\Router;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Dingo\Api\Contract\Routing\Adapter;

class Cache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'api:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description = 'Create a route cache file for faster route registration';

    /**
     * Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Router instance.
     *
     * @var \Dingo\Api\Routing\Router
     */
    private $router;

    /**
     * Adapter instance.
     *
     * @var \Dingo\Api\Contract\Routing\Adapter
     */
    private $adapter;

    /**
     * Create a new cache command instance.
     *
     * @param \Illuminate\Filesystem\Filesystem   $files
     * @param \Dingo\Api\Routing\Router           $router
     * @param \Dingo\Api\Contract\Routing\Adapter $adapter
     *
     * @return void
     */
    public function __construct(Filesystem $files, Router $router, Adapter $adapter)
    {
        $this->files = $files;
        $this->router = $router;
        $this->adapter = $adapter;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->callSilent('route:clear');

        $this->call('route:cache');

        $routes = $this->router->getAdapterRoutes();

        foreach ($routes as $collection) {
            foreach ($collection as $route) {
                $this->adapter->prepareRouteForSerialization($route);
            }
        }

        $stub = "app('api.router')->setAdapterRoutes(unserialize(base64_decode('{{routes}}')));";
        $path = $this->laravel->getCachedRoutesPath();

        if (! $this->files->exists($path)) {
            $stub = "<?php\n\n$stub";
        }

        $this->files->append(
            $path,
            str_replace('{{routes}}', base64_encode(serialize($routes)), $stub)
        );
    }
}
