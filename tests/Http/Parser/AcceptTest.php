<?php

namespace Dingo\Api\Tests\Http\Parser;

use Dingo\Api\Http\Request;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Http\Parser\Accept;

class AcceptTest extends PHPUnit_Framework_TestCase
{
    public function testParsingInvalidAcceptReturnsDefaults()
    {
        $parser = new Accept('vnd', 'api', 'v1', 'json');

        $accept = $parser->parse($this->createRequest('foo', 'GET', ['accept' => 'application/vnd.foo.v2+xml']));

        $this->assertEquals('api', $accept['subtype']);
        $this->assertEquals('v1', $accept['version']);
        $this->assertEquals('json', $accept['format']);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedMessage Accept header could not be properly parsed because of a strict matching process.
     */
    public function testStrictlyParsingInvalidAcceptHeaderThrowsException()
    {
        $parser = new Accept('vnd', 'api', 'v1', 'json');

        $accept = $parser->parse($this->createRequest('foo', 'GET', ['accept' => 'application/vnd.foo.v2+xml']), true);
    }

    public function testParsingValidAcceptReturnsHeaderValues()
    {
        $parser = new Accept('vnd', 'api', 'v1', 'json');

        $accept = $parser->parse($this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v2+xml']));

        $this->assertEquals('api', $accept['subtype']);
        $this->assertEquals('v2', $accept['version']);
        $this->assertEquals('xml', $accept['format']);
    }

    public function testApiVersionWithoutVSuffix()
    {
        $parser = new Accept('vnd', 'api', '1.0', 'json');

        $accept = $parser->parse($this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.1.0+xml']));

        $this->assertEquals('api', $accept['subtype']);
        $this->assertEquals('1.0', $accept['version']);
        $this->assertEquals('xml', $accept['format']);
    }

    protected function createRequest($uri, $method, array $headers = [])
    {
        $request = Request::create($uri, $method);

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        return $request;
    }
}
