<?php

namespace Dingo\Api\Console;

use Illuminate\Console\Command;
use Dingo\Api\Generator\Writer;
use Dingo\Api\Generator\Generator;

class DocsCommand extends Command
{
    /**
     * Generator instance.
     *
     * @var \Dingo\Api\Generator\Generator
     */
    protected $docs;

    /**
     * Writer instance.
     *
     * @var \Dingo\Api\Generator\Writer
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
     * @param \Dingo\Api\Generator\Generator $docs
     * @param \Dingo\Api\Generator\Writer    $writer
     *
     * @return void
     */
    public function __construct(Generator $docs, Writer $writer)
    {
        parent::__construct();

        $this->docs = $docs;
        $this->writer = $writer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $contents = $this->docs->generate($this->argument('name'), $this->argument('version'));

        if ($file = $this->option('file')) {
            $this->writer->write($contents, $file);

            return $this->info('Documentation was generated successfully.');
        }

        return $this->line($contents);
    }
}
