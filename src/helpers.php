<?php


if (! function_exists('version')) {
    /**
     * Set the version to generate API URLs to.
     *
     * @param string $version
     *
     * @return \Dingo\Api\Routing\UrlGenerator
     */
    function version($version)
    {
        return app('api.url')->version($version);
    }
}

if (! function_exists('api_route')) {
    /**
     * Generate a API URL to the named route.
     *
     * @param string $version
     * @param string $name
     *
     * @return string
     */
    function api_route($version, $name)
    {
        return app('Dingo\Api\Routing\UrlGenerator')->version($version)->route($name);
    }
}
