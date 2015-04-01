<?php

namespace Dingo\Api\Http;

use Dingo\Api\Properties;

class Matcher
{
    /**
     * Properties instance.
     *
     * @var \Dingo\Api\Properties
     */
    protected $properties;

    /**
     * Create a new matcher instance.
     *
     * @param \Dingo\Api\Properties $properties
     *
     * @return void
     */
    public function __construct(Properties $properties)
    {
        $this->properties = $properties;
    }

    /**
     * Matches domain if is set on route group.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function matchDomain($request)
    {
        return $this->properties->getDomain() && $request->header('host') == $this->properties->getDomain();
    }

    /**
     * Matches prefix if is set in route group.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function matchPrefix($request)
    {
        $prefix = $this->filterAndExplode($this->properties->getPrefix());

        $path = $this->filterAndExplode($request->getPathInfo());

        return $prefix == array_slice($path, 0, count($prefix));
    }

    /**
     * Explode array on slash and remove empty values.
     *
     * @param array $array
     *
     * @return array
     */
    protected function filterAndExplode($array)
    {
        return array_filter(explode('/', $array));
    }
}
