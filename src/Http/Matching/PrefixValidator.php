<?php

namespace Dingo\Api\Http\Matching;

use Dingo\Api\Http\Request;

class PrefixValidator implements ValidatorInterface
{
    protected $prefix;

    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    public function matches(Request $request)
    {
        $prefix = $this->filterAndExplode($this->prefix);

        $path = $this->filterAndExplode($request->getPathInfo());

        return ! is_null($this->prefix) && $prefix == array_slice($path, 0, count($prefix));
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
