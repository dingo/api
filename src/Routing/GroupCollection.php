<?php

namespace Dingo\Api\Routing;

use Dingo\Api\Properties;
use Illuminate\Http\Request;

class GroupCollection
{
    /**
     * API properties instance.
     *
     * @var \Dingo\Api\Properties
     */
    protected $properties;

    /**
     * Array of API route collections.
     *
     * @var array
     */
    protected $groups = [];

    /**
     * Array of API versions.
     *
     * @var array
     */
    protected $versions = [];

    /**
     * Create a new version collection instance.
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
     * Add a group to the collection.
     *
     * @param string $version
     * @param array  $options
     *
     * @return \Dingo\Api\Routing\RouteCollection
     */
    public function add($version, array $options)
    {
        $this->versions[] = $version;

        return $this->groups[] = new RouteCollection($version, $options);
    }

    /**
     * Determine if the version exists on the collection.
     *
     * @param string $version
     *
     * @return bool
     */
    public function has($version)
    {
        return in_array($version, $this->versions);
    }

    /**
     * Get a matching API route collection from the request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Dingo\Api\Routing\RouteCollection|null
     */
    public function getByRequest(Request $request)
    {
        return array_first($this->groups, function ($key, $collection) use ($request) {
            return $collection->matchesRequest($request);
        });
    }

    /**
     * Get the default API route collection.
     *
     * @return \Dingo\Api\Routing\RouteCollection|null
     */
    public function getDefault()
    {
        return $this->getByVersion($this->properties->getVersion());
    }

    /**
     * Get an API route collection for a given version.
     *
     * @param string $version
     *
     * @return \Dingo\Api\Routing\RouteCollection|null
     */
    public function getByVersion($version)
    {
        return array_first($this->groups, function ($key, $collection) use ($version) {
            return $collection->matchesVersion($version);
        });
    }

    /**
     * Get an API route collection for a given domain and optionally a version.
     *
     * @param string $domain
     * @param string $version
     *
     * @return \Dingo\Api\Routing\RouteCollection|null
     */
    public function getByDomain($domain, $version = null)
    {
        return array_first($this->groups, function ($key, $collection) use ($domain, $version) {
            if (isset($version) && ! $collection->matchesVersion($version)) {
                return false;
            }

            return $collection->matchesDomain($domain);
        });
    }

    /**
     * Get an aPI route collection for a given domain or a given version.
     *
     * @param string $domain
     * @param string $version
     *
     * @return \Dingo\Api\Routing\RouteCollection|null
     */
    public function getByDomainOrVersion($domain, $version)
    {
        if (is_null($domain)) {
            return $this->getByVersion($version);
        }

        return $this->getByDomain($domain, $version);
    }

    /**
     * Get an API route collection for a given array of options.
     *
     * @param array $options
     *
     * @return array
     */
    public function getByOptions($options)
    {
        return array_where($this->groups, function ($key, $collection) use ($options) {
            if ($collection->matchesVersion($options['version'])) {
                // If a domain is present in the options then we'll attempt to match the domain
                // on the group. If we can't then we'll bail out, otherwise all should be good.
                if (isset($options['domain']) && ! $collection->matchesDomain($options['domain'])) {
                    return false;
                }

                return true;
            }

            return false;
        });
    }

    /**
     * Get all routes.
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->groups;
    }

    /**
     * Determine if the version collection is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->groups);
    }
}
