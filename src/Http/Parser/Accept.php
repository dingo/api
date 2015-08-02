<?php

namespace Dingo\Api\Http\Parser;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Accept implements Parser
{
    /**
     * API vendor.
     *
     * @var string
     */
    protected $vendor;

    /**
     * Default version.
     *
     * @var string
     */
    protected $version;

    /**
     * Default format.
     *
     * @var string
     */
    protected $format;

    /**
     * Create a new accept parser instance.
     *
     * @param string $vendor
     * @param string $version
     * @param string $format
     *
     * @return void
     */
    public function __construct($vendor, $version, $format)
    {
        $this->vendor = $vendor;
        $this->version = $version;
        $this->format = $format;
    }

    /**
     * Parse the accept header on the incoming request. If strict is enabled
     * then the accept header must be available and must be a valid match.
     *
     * @param \Illuminate\Http\Request $request
     * @param bool                     $strict
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return array
     */
    public function parse(Request $request, $strict = false)
    {
        $default = 'application/vnd.'.$this->vendor.'.'.$this->version.'+'.$this->format;

        $pattern = '/application\/vnd\.('.$this->vendor.')\.(v?[\d\.]+)\+([\w]+)/';

        if (! preg_match($pattern, $request->header('accept'), $matches)) {
            if ($strict) {
                throw new BadRequestHttpException('Accept header could not be properly parsed because of a strict matching process.');
            }

            preg_match($pattern, $default, $matches);
        }

        return array_combine(['vendor', 'version', 'format'], array_slice($matches, 1));
    }
}
