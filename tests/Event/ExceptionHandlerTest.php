<?php

namespace Dingo\Api\Tests\Event;

use Mockery;
use Exception;
use Illuminate\Http\Response;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Event\ExceptionHandler;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionHandlerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->handler = Mockery::mock('Dingo\Api\Exception\Handler');
        $this->event = new ExceptionHandler($this->handler);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage rethrow the exception
     */
    public function testExceptionNotHandledByHandlerAndRethrownWhenNotHttpException()
    {
        $exception = new Exception('rethrow the exception');

        $this->handler->shouldReceive('willHandle')->once()->with($exception)->andReturn(false);
        $this->event->handle($exception);
    }

    public function testExceptionNotHandledByHandlerAndResponseGenerated()
    {
        $exception = new HttpException(404);

        $this->handler->shouldReceive('willHandle')->once()->with($exception)->andReturn(false);
        $response = $this->event->handle($exception);

        $this->assertInstanceOf('Dingo\Api\Http\Response', $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('{"message":"404 Not Found","status_code":404}', $response->getContent());
    }

    public function testExceptionNotHandledByHandlerAndResponseGeneratedWithErrors()
    {
        $exception = new ResourceException('error', ['field' => 'error'], null, [], 2);

        $this->handler->shouldReceive('willHandle')->once()->with($exception)->andReturn(false);
        $response = $this->event->handle($exception);

        $this->assertInstanceOf('Dingo\Api\Http\Response', $response);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('{"message":"error","status_code":422,"errors":{"field":["error"]},"code":2}', $response->getContent());
    }

    public function testExceptionHandledByHandler()
    {
        $exception = new Exception('error');

        $this->handler->shouldReceive('willHandle')->once()->with($exception)->andReturn(true);
        $this->handler->shouldReceive('handle')->once()->with($exception)->andReturn(new Response('error', 404));
        $response = $this->event->handle($exception);

        $this->assertInstanceOf('Dingo\Api\Http\Response', $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('error', $response->getContent());
    }

    public function testExceptionHandledWithDebug()
    {
        $this->event->setDebug(true);

        $this->handler->shouldReceive('willHandle')->once()->with($exception = new HttpException(404))->andReturn(false);

        $response = $this->event->handle($exception);

        $original = $response->getOriginalContent();

        $this->assertArrayHasKey('debug', $original);
        $this->assertEquals(74, $original['debug']['line']);
        $this->assertEquals('ExceptionHandlerTest.php', basename($original['debug']['file']));
    }
}
