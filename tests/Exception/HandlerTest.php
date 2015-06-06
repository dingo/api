<?php

namespace Dingo\Api\Tests\Exception;

use Mockery as m;
use RuntimeException;
use Illuminate\Http\Response;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Exception\Handler;
use Dingo\Api\Http\Request as ApiRequest;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request as IlluminateRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HandlerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->parentHandler = m::mock('Illuminate\Contracts\Debug\ExceptionHandler');

        $this->exceptionHandler = new Handler($this->parentHandler, [
            'message' => ':message',
            'errors' => ':errors',
            'code' => ':code',
            'status_code' => ':status_code',
            'debug' => ':debug'
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

    public function testUsingMultidimensionalArrayForGenericResponse()
    {
        $this->exceptionHandler->setErrorFormat([
            'error' => [
                'message' => ':message',
                'errors' => ':errors',
                'code' => ':code',
                'status_code' => ':status_code',
                'debug' => ':debug'
            ]
        ]);

        $response = $this->exceptionHandler->handle(new HttpException(404, 'bar'));

        $this->assertInstanceOf('Illuminate\Http\Response', $response);
        $this->assertEquals('{"error":{"message":"bar","status_code":404}}', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRegularExceptionsAreHandledByGenericHandler()
    {
        $response = $this->exceptionHandler->handle(new RuntimeException('Uh oh'));

        $this->assertEquals('{"message":"Uh oh","status_code":500}', $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testResourceExceptionErrorsAreIncludedInResponse()
    {
        $response = $this->exceptionHandler->handle(new ResourceException('bar', ['foo' => 'bar'], null, [], 10));

        $this->assertInstanceOf('Illuminate\Http\Response', $response);
        $this->assertEquals('{"message":"bar","errors":{"foo":["bar"]},"code":10,"status_code":422}', $response->getContent());
        $this->assertEquals(422, $response->getStatusCode());
    }

    public function testExceptionTraceIncludedInResponse()
    {
        $this->exceptionHandler->setDebug(true);

        $response = $this->exceptionHandler->handle(new HttpException(404, 'bar'));

        $object = json_decode($response->getContent());

        $this->assertObjectHasAttribute('debug', $object);
    }

    public function testHttpExceptionsWithNoMessageUseStatusCodeMessage()
    {
        $response = $this->exceptionHandler->handle(new HttpException(404));

        $this->assertInstanceOf('Illuminate\Http\Response', $response);
        $this->assertEquals('{"message":"404 Not Found","status_code":404}', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testIlluminateRequestsAreHandledByParentHandler()
    {
        $request = IlluminateRequest::create('foo', 'GET');
        $exception = new HttpException(404);

        $this->parentHandler->shouldReceive('render')->with($request, $exception)->andReturn('foo');

        $this->assertEquals('foo', $this->exceptionHandler->render($request, $exception));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testApiRequestsRethrowExceptions()
    {
        $request = ApiRequest::create('foo', 'GET');
        $exception = new HttpException(404);

        $this->exceptionHandler->render($request, $exception);
    }

    public function testSettingUserDefinedReplacements()
    {
        $this->exceptionHandler->setReplacements([':foo' => 'bar']);
        $this->exceptionHandler->setErrorFormat(['bing' => ':foo']);

        $response = $this->exceptionHandler->handle(new HttpException(404));

        $this->assertEquals('{"bing":"bar"}', $response->getContent());
    }
}
