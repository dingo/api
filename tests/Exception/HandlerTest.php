<?php

namespace Dingo\Api\Tests\Exception;

use Mockery as m;
use RuntimeException;
use Illuminate\Http\Response;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Exception\Handler;
use Dingo\Api\Http\Request as ApiRequest;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HandlerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->parentHandler = m::mock(ExceptionHandler::class);
        $this->exceptionHandler = new Handler($this->parentHandler, [
            'message' => ':message',
            'errors' => ':errors',
            'code' => ':code',
            'status_code' => ':status_code',
            'debug' => ':debug',
        ], false);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testRegisterExceptionHandler()
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            //
        });
        $this->assertArrayHasKey(HttpException::class, $this->exceptionHandler->getHandlers());
    }

    public function testExceptionHandlerHandlesException()
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            return new Response('foo', 404);
        });

        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testExceptionHandlerHandlesExceptionAndCreatesNewResponse()
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            return 'foo';
        });

        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testExceptionHandlerReturnsGenericWhenNoMatchingHandler()
    {
        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('{"message":"bar","status_code":404}', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testUsingMultidimensionalArrayForGenericResponse()
    {
        $this->exceptionHandler->setErrorFormat([
            'error' => [
                'message' => ':message',
                'errors' => ':errors',
                'code' => ':code',
                'status_code' => ':status_code',
                'debug' => ':debug',
            ],
        ]);

        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('{"error":{"message":"bar","status_code":404}}', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRegularExceptionsAreHandledByGenericHandler()
    {
        $exception = new RuntimeException('Uh oh');

        $response = $this->exceptionHandler->handle($exception);

        $this->assertEquals('{"message":"Uh oh","status_code":500}', $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testResourceExceptionErrorsAreIncludedInResponse()
    {
        $exception = new ResourceException('bar', ['foo' => 'bar'], null, [], 10);

        $response = $this->exceptionHandler->handle($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('{"message":"bar","errors":{"foo":["bar"]},"code":10,"status_code":422}', $response->getContent());
        $this->assertEquals(422, $response->getStatusCode());
    }

    public function testExceptionTraceIncludedInResponse()
    {
        $this->exceptionHandler->setDebug(true);

        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        $object = json_decode($response->getContent());

        $this->assertObjectHasAttribute('debug', $object);
    }

    public function testHttpExceptionsWithNoMessageUseStatusCodeMessage()
    {
        $exception = new HttpException(404);

        $response = $this->exceptionHandler->handle($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('{"message":"404 Not Found","status_code":404}', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testExceptionsHandledByRenderAreReroutedThroughHandler()
    {
        $request = ApiRequest::create('foo', 'GET');

        $exception = new HttpException(404);

        $response = $this->exceptionHandler->render($request, $exception);

        $this->assertEquals('{"message":"404 Not Found","status_code":404}', $response->getContent());
    }

    public function testSettingUserDefinedReplacements()
    {
        $this->exceptionHandler->setReplacements([':foo' => 'bar']);
        $this->exceptionHandler->setErrorFormat(['bing' => ':foo']);

        $exception = new HttpException(404);

        $response = $this->exceptionHandler->handle($exception);

        $this->assertEquals('{"bing":"bar"}', $response->getContent());
    }
}
