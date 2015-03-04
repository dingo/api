<?php

namespace Dingo\Api\tests\Http;

use Dingo\Api\Http\Response;
use PHPUnit_Framework_TestCase;
use stdClass;

class ResponseTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     * @expectedExceptionMessage Unable to format response according to Accept header.
     */
    public function testGettingInvalidFormatterThrowsException()
    {
        Response::getFormatter('json');
    }

    public function testNonCastableObjectsSetAsOriginalContent()
    {
        $object = new stdClass();
        $object->id = 'test';

        $response = new Response($object);

        $this->assertNull($response->getContent());
        $this->assertSame($object, $response->getOriginalContent());
    }
}
