<?php

namespace Dingo\Api\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Console\Kernel;

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
     * Create a new cache command instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;

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

        $app = $this->getFreshApplication();

        $this->call('route:cache');

        $routes = $app['api.router']->getAdapterRoutes();

        foreach ($routes as $collection) {
            foreach ($collection as $route) {
                $app['api.router.adapter']->prepareRouteForSerialization($route);
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

    /**
     * Get a fresh application instance.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    protected function getFreshApplication()
    {
        $app = require $this->laravel->basePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
