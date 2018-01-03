<?php

namespace Dingo\Api\Tests\Exception;

use Mockery as m;
use RuntimeException;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use Dingo\Api\Exception\Handler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Dingo\Api\Http\Request as ApiRequest;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HandlerTest extends TestCase
{
    protected $parentHandler;
    protected $exceptionHandler;

    public function setUp()
    {
        $this->parentHandler = m::mock('Illuminate\Contracts\Debug\ExceptionHandler');
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
        $this->assertArrayHasKey(\Symfony\Component\HttpKernel\Exception\HttpException::class, $this->exceptionHandler->getHandlers());
    }

    public function testExceptionHandlerHandlesException()
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            return new Response('foo', 404);
        });

        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        $this->assertSame('foo', $response->getContent());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testExceptionHandlerHandlesExceptionAndCreatesNewResponse()
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            return 'foo';
        });

        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('foo', $response->getContent());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testExceptionHandlerHandlesExceptionWithRedirectResponse()
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            return new RedirectResponse('foo');
        });

        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('foo', $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testExceptionHandlerHandlesExceptionWithJsonResponse()
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            return new JsonResponse(['foo' => 'bar'], 404);
        });

        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('{"foo":"bar"}', $response->getContent());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testExceptionHandlerReturnsGenericWhenNoMatchingHandler()
    {
        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('{"message":"bar","status_code":404}', $response->getContent());
        $this->assertSame(404, $response->getStatusCode());
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
        $this->assertSame('{"error":{"message":"bar","status_code":404}}', $response->getContent());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRegularExceptionsAreHandledByGenericHandler()
    {
        $exception = new RuntimeException('Uh oh');

        $response = $this->exceptionHandler->handle($exception);

        $this->assertSame('{"message":"Uh oh","status_code":500}', $response->getContent());
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testResourceExceptionErrorsAreIncludedInResponse()
    {
        $exception = new ResourceException('bar', ['foo' => 'bar'], null, [], 10);

        $response = $this->exceptionHandler->handle($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('{"message":"bar","errors":{"foo":["bar"]},"code":10,"status_code":422}', $response->getContent());
        $this->assertSame(422, $response->getStatusCode());
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
        $this->assertSame('{"message":"404 Not Found","status_code":404}', $response->getContent());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testExceptionsHandledByRenderAreReroutedThroughHandler()
    {
        $request = ApiRequest::create('foo', 'GET');

        $exception = new HttpException(404);

        $response = $this->exceptionHandler->render($request, $exception);

        $this->assertSame('{"message":"404 Not Found","status_code":404}', $response->getContent());
    }

    public function testSettingUserDefinedReplacements()
    {
        $this->exceptionHandler->setReplacements([':foo' => 'bar']);
        $this->exceptionHandler->setErrorFormat(['bing' => ':foo']);

        $exception = new HttpException(404);

        $response = $this->exceptionHandler->handle($exception);

        $this->assertSame('{"bing":"bar"}', $response->getContent());
    }
}
