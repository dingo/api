<?php

namespace Dingo\Api\Generator;

use ReflectionClass;
use Dingo\Api\Http\Request;
use Dingo\Api\Routing\Router;
use Illuminate\Support\Collection;
use Dingo\Api\Generator\Annotation;
use Illuminate\Contracts\Container\Container;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

class Generator
{
    /**
     * Container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Router instance.
     *
     * @var \Dingo\Api\Routing\Router
     */
    protected $router;

    /**
     * Simple annotation reader instance.
     *
     * @var \Doctrine\Common\Annotations\SimpleAnnotationReader
     */
    protected $reader;
    /**
     * Create a new generator instance.
     *
     * @param \Illuminate\Contracts\Container\Container           $container
     * @param \Dingo\Api\Routing\Router                           $router
     * @param \Doctrine\Common\Annotations\SimpleAnnotationReader $reader
     *
     * @return void
     */
    public function __construct(Container $container, Router $router, SimpleAnnotationReader $reader)
    {
        $this->container = $container;
        $this->router = $router;
        $this->reader = $reader;

        $this->registerAnnotationLoader();
    }

    /**
     * Register the annotation loader.
     *
     * @return void
     */
    protected function registerAnnotationLoader()
    {
        $this->reader->addNamespace('Dingo\\Api\\Generator\\Annotation');
        $this->reader->addNamespace('Dingo\\Api\\Generator\\Annotation\\Method');

        AnnotationRegistry::registerLoader(function ($class) {
            $path = __DIR__.'/'.str_replace(['Dingo\\Api\\Generator\\', '\\'], ['', DIRECTORY_SEPARATOR], $class).'.php';

            if (file_exists($path)) {
                require_once $path;

                return true;
            }
        });
    }

    /**
     * Generate documentation with the name and version.
     *
     * @param string $name
     * @param string $version
     *
     * @return bool
     */
    public function generate($name, $version)
    {
        $this->container['request'] = Request::createFromExisting($this->container['request']);

        $resources = $this->getControllerInstances()->map(function ($controller) use ($version) {
            $controller = new ReflectionClass($controller);

            $actions = new Collection;

            // Spin through all the methods on the controller and compare the version
            // annotation (if supplied) with the version given for the generation.
            // We'll also build up an array of actions on each resource.
            foreach ($controller->getMethods() as $method) {
                if ($versionAnnotation = $this->reader->getMethodAnnotation($method, Annotation\Versions::class)) {
                    if (! in_array($version, $versionAnnotation->value)) {
                        continue;
                    }
                }

                if ($annotations = $this->reader->getMethodAnnotations($method)) {
                    $actions[] = new Action($method, new Collection($annotations));
                }
            }

            $annotations = new Collection($this->reader->getClassAnnotations($controller));

            return new Resource($controller->getName(), $controller, $annotations, $actions);
        });

        return $this->generateContentsFromResources($resources, $name);
    }

    /**
     * Generate the documentation contents from the resources collection.
     *
     * @param \Illuminate\Support\Collection $resources
     * @param string                         $name
     *
     * @return string
     */
    protected function generateContentsFromResources(Collection $resources, $name)
    {
        $contents = '';

        $contents .= $this->getFormat();
        $contents .= $this->line(2);
        $contents .= sprintf('# %s', $name);
        $contents .= $this->line(2);

        $resources->each(function ($resource) use (&$contents) {
            $contents .= $resource->getDefinition();

            if ($description = $resource->getDescription()) {
                $contents .= $this->line();
                $contents .= $description;
            }

            if (($parameters = $resource->getParameters()) && ! $parameters->isEmpty()) {
                $this->appendParameters($contents, $parameters);
            }

            $resource->getActions()->each(function ($action) use (&$contents) {
                $contents .= $this->line(2);
                $contents .= $action->getDefinition();

                if ($description = $action->getDescription()) {
                    $contents .= $this->line();
                    $contents .= $description;
                }

                if (($parameters = $action->getParameters()) && ! $parameters->isEmpty()) {
                    $this->appendParameters($contents, $parameters);
                }

                if ($request = $action->getRequest()) {
                    $this->appendRequest($contents, $request);
                }

                if ($response = $action->getResponse()) {
                    $this->appendResponse($contents, $response);
                }

                if ($transaction = $action->getTransaction()) {
                    foreach ($transaction->value as $value) {
                        if ($value instanceof Annotation\Request) {
                            $this->appendRequest($contents, $value);
                        } elseif ($value instanceof Annotation\Response) {
                            $this->appendResponse($contents, $value);
                        } else {
                            throw new \RuntimeException('Unsupported annotation type given in transaction.');
                        }
                    }
                }
            });
        });

        return $contents;
    }

    /**
     * Append the parameters subsection to a resource or action.
     *
     * @param string                         $contents
     * @param \Illuminate\Support\Collection $parameters
     *
     * @return void
     */
    protected function appendParameters(&$contents, Collection $parameters)
    {
        $this->appendSection($contents, 'Parameters');

        $parameters->each(function ($parameter) use (&$contents) {
            $contents .= $this->line();
            $contents .= $this->tab();
            $contents .= sprintf(
                '+ %s (%s, %s) - %s',
                $parameter->identifier,
                $parameter->type,
                $parameter->required ? 'required' : 'optional',
                $parameter->description
            );

            if (isset($parameter->default)) {
                $this->appendSection($contents, sprintf('Default: %s', $parameter->default), 2, 1);
            }
        });
    }

    /**
     * Append a response subsection to an action.
     *
     * @param string                                   $contents
     * @param \Dingo\Api\Generator\Annotation\Response $response
     *
     * @return void
     */
    protected function appendResponse(&$contents, Annotation\Response $response)
    {
        $this->appendSection($contents, sprintf('Response %s', $response->statusCode));

        if (isset($response->contentType)) {
            $contents .= ' ('.$response->contentType.')';
        }

        if (! empty($request->headers)) {
            $this->appendHeaders($contents, $request->headers);
        }

        $this->appendBody($contents, $this->prepareBody($response->body, $response->contentType));
    }

    /**
     * Append a request subsection to an action.
     *
     * @param string                                  $contents
     * @param \Dingo\Api\Generator\Annotation\Request $request
     *
     * @return void
     */
    protected function appendRequest(&$contents, $request)
    {
        $this->appendSection($contents, 'Request');

        if (isset($request->identifier)) {
            $contents .= ' '.$request->identifier;
        }

        $contents .= ' ('.$request->contentType.')';

        if (! empty($request->headers)) {
            $this->appendHeaders($contents, $request->headers);
        }

        if (isset($request->body)) {
            $this->appendBody($contents, $this->prepareBody($request->body, $request->contentType));
        }
    }

    /**
     * Append a body subsection to an action.
     *
     * @param string $contents
     * @param string $body
     *
     * @return void
     */
    protected function appendBody(&$contents, $body)
    {
        $this->appendSection($contents, 'Body', 1, 1);

        $contents .= $this->line(2);

        $line = strtok($body, "\r\n");

        while ($line !== false) {
            $contents .= $this->tab(3).$line.$this->line();

            $line = strtok("\r\n");
        }
    }

    /**
     * Append a headers subsection to an action.
     *
     * @param string $contents
     * @param array  $response
     *
     * @return void
     */
    protected function appendHeaders(&$contents, array $headers)
    {
        $this->appendSection($contents, 'Headers', 1, 1);

        $contents .= $this->line();

        foreach ($headers as $header => $value) {
            $contents .= $this->line().$this->tab(3).sprintf('%s: %s', $header, $value);
        }

        $contents .= $this->line();
    }

    /**
     * Append a subsection to an action.
     *
     * @param string $contents
     * @param string $name
     * @param int    $indent
     * @param int    $lines
     *
     * @return void
     */
    protected function appendSection(&$contents, $name, $indent = 0, $lines = 2)
    {
        $contents .= $this->line($lines);
        $contents .= $this->tab($indent);
        $contents .= '+ '.$name;
    }

    /**
     * Prepare a body.
     *
     * @param string $body
     * @param string $contentType
     *
     * @return string
     */
    protected function prepareBody($body, $contentType)
    {
        if ($contentType == 'application/json') {
            return json_encode($body, JSON_PRETTY_PRINT);
        }

        return $body;
    }

    /**
     * Create a new line character.
     *
     * @param int $repeat
     *
     * @return string
     */
    protected function line($repeat = 1)
    {
        return str_repeat("\n", $repeat);
    }

    /**
     * Create a tab character.
     *
     * @param int $repeat
     *
     * @return string
     */
    protected function tab($repeat = 1)
    {
        return str_repeat("    ", $repeat);
    }

    /**
     * Get the API Blueprint format.
     *
     * @return string
     */
    protected function getFormat()
    {
        return 'FORMAT: 1A';
    }

    /**
     * Get all the controller instances.
     *
     * @return array
     */
    protected function getControllerInstances()
    {
        $controllers = new Collection;

        foreach ($this->router->getRoutes() as $collections) {
            foreach ($collections as $route) {
                $route = $this->router->createRoute($route);

                if ($controller = $route->getController()) {
                    $controllers[] = $controller;
                }
            }
        }

        return $controllers;
    }
}
