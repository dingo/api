<?php

namespace Dingo\Api\Tests\Exception;

use Illuminate\Http\Response;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Exception\Handler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HandlerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->exceptionHandler = new Handler([
            'message' => ':message',
            'errors' => ':errors',
            'code' => ':code',
            'status_code' => ':status_code',
            'debug' => ':debug'
        ], false);
    }

    public function testRegisterExceptionHandler()
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            //
        });
        $this->assertArrayHasKey('Symfony\Component\HttpKernel\Exception\HttpException', $this->exceptionHandler->getHandlers());
    }

    public function testExceptionHandlerHandlesException()
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            return new Response('foo', 404);
        });

        $response = $this->exceptionHandler->handle(new HttpException(404, 'bar'));

        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testExceptionHandlerHandlesExceptionAndCreatesNewResponse()
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            return 'foo';
        });

        $response = $this->exceptionHandler->handle(new HttpException(404, 'bar'));

        $this->assertInstanceOf('Illuminate\Http\Response', $response);
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testExceptionHandlerReturnsGenericWhenNoMatchingHandler()
    {
        $response = $this->exceptionHandler->handle(new HttpException(404, 'bar'));

        $this->assertInstanceOf('Illuminate\Http\Response', $response);
        $this->assertEquals('{"message":"bar","status_code":404}', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }
}
