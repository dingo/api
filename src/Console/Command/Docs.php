<?php

namespace Dingo\Api\Console\Command;

use Dingo\Blueprint\Writer;
use Dingo\Api\Routing\Router;
use Dingo\Blueprint\Blueprint;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class Docs extends Command
{
    /**
     * Router instance.
     *
     * @var \Dingo\Api\Routing\Router
     */

    protected $router;

    /**
     * Blueprint instance.
     *
     * @var \Dingo\Blueprint\Blueprint
     */
    protected $docs;

    /**
     * Writer instance.
     *
     * @var \Dingo\Blueprint\Writer
     */
    protected $writer;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:docs {name : The name of the generated documentation}
                                     {version : Version of the documentation to be generated}
                                     {--file= : Output the generated documentation to a file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API documentation from annotated controllers';

    /**
     * Create a new docs command instance.
     *
     * @param \Dingo\Api\Routing\Router  $router
     * @param \Dingo\Blueprint\Blueprint $blueprint
     * @param \Dingo\Blueprint\Writer    $writer
     *
     * @return void
     */
    public function __construct(Router $router, Blueprint $blueprint, Writer $writer)
    {
        parent::__construct();

        $this->router = $router;
        $this->blueprint = $blueprint;
        $this->writer = $writer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $contents = $this->blueprint->generate($this->getControllers(), $this->argument('name'), $this->argument('version'));

        if ($file = $this->option('file')) {
            $this->writer->write($contents, $file);

            return $this->info('Documentation was generated successfully.');
        }

        return $this->line($contents);
    }

    /**
     * Get all the controller instances.
     *
     * @return array
     */
    protected function getControllers()
    {
        $controllers = new Collection;

        foreach ($this->router->getRoutes() as $collections) {
            foreach ($collections as $route) {
                if ($controller = $route->getController()) {
                    if (!$controllers->contains($controller)) {
                        $controllers->push($controller);
                    }
                }
            }
        }

        return $controllers;
    }
}
