<?php

namespace Dingo\Api;

class Properties
{
    /**
     * API version.
     *
     * @var string
     */
    protected $version;

    /**
     * API prefix.
     *
     * @var string
     */
    protected $prefix;

    /**
     * API domain.
     *
     * @var string
     */
    protected $domain;

    /**
     * API vendor.
     *
     * @var string
     */
    protected $vendor;

    /**
     * API format.
     *
     * @var string
     */
    protected $format;

    /**
     * Create a new properties instance.
     *
     * @param string $version
     * @param string $prefix
     * @param string $domain
     * @param string $vendor
     * @param string $format
     *
     * @return void
     */
    public function __construct($version = 'v1', $prefix = null, $domain = null, $vendor = null, $format = 'json')
    {
        $this->version = $version;
        $this->prefix = $prefix;
        $this->domain = $domain;
        $this->vendor = $vendor;
        $this->format = $format;
    }

    /**
     * Get the API version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Get the API prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Get the API domain.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Get the API vendor.
     *
     * @return string
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * Get the API format.
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set the API version.
     *
     * @param string $version
     *
     * @return void
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Set the API format.
     *
     * @param string $format
     *
     * @return void
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Set the API vendor.
     *
     * @param string $vendor
     *
     * @return void
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
    }

    /**
     * Set the API domain.
     *
     * @param string $domain
     *
     * @return void
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Set the API prefix.
     *
     * @param string $prefix
     *
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }
}
