<?php

namespace Dingo\Api\Tests\Http;

use Dingo\Api\Http\Response;
use PHPUnit_Framework_TestCase;

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
}
