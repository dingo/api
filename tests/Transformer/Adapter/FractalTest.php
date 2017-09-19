<?php

namespace Dingo\Api\Tests\Transformer\Adapter;

use PHPUnit_Framework_TestCase;
use Dingo\Api\Http\Request;
use Dingo\Api\Transformer\Adapter\Fractal;
use League\Fractal\Manager as FractalManager;

class FractalTest extends PHPUnit_Framework_TestCase
{
    protected $fractal;

    protected function setUp()
    {
        $this->fractal = new Fractal(new FractalManager());
    }

    public function testParseFractalIncludes()
    {
        $request = Request::create('/?include=foo,bar', 'GET');
        $this->fractal->parseFractalIncludes($request);
        $requestedIncludes = $this->fractal->getFractal()->getRequestedIncludes();

        $this->assertEquals(['foo', 'bar'], $requestedIncludes);
    }

    public function testParseFractalIncludesWithSpaces()
    {
        $request = Request::create('/?include=foo, bar', 'GET');
        $this->fractal->parseFractalIncludes($request);
        $requestedIncludes = $this->fractal->getFractal()->getRequestedIncludes();

        $this->assertEquals(['foo', 'bar'], $requestedIncludes);
    }
}
