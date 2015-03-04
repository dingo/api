<?php

namespace Dingo\Api\tests\Http\ResponseFormat;

use Dingo\Api\Http\Response;
use Dingo\Api\Http\ResponseFormat\JsonpResponseFormat;
use Dingo\Api\Tests\Stubs\EloquentModelStub;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Mockery;
use PHPUnit_Framework_TestCase;

class JsonpResponseFormatTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $formatter = new JsonpResponseFormat();
        $formatter->setRequest(Request::create('GET', '/', ['callback' => 'foo']));

        Response::setFormatters(['json' => $formatter]);
    }

    public function tearDown()
    {
        Mockery::close();

        Response::setFormatters([]);
    }

    public function testMorphingEloquentModel()
    {
        $response = (new Response(new EloquentModelStub()))->morph();

        $this->assertEquals('foo({"app_user":{"foo":"bar"}});', $response->getContent());
    }

    public function testMorphingEloquentCollection()
    {
        $response = (new Response(new Collection([new EloquentModelStub(), new EloquentModelStub()])))->morph();

        $this->assertEquals('foo({"app_users":[{"foo":"bar"},{"foo":"bar"}]});', $response->getContent());
    }

    public function testMorphingEmptyEloquentCollection()
    {
        $response = (new Response(new Collection()))->morph();

        $this->assertEquals('foo([]);', $response->getContent());
    }

    public function testMorphingString()
    {
        $response = (new Response('foo'))->morph();

        $this->assertEquals('foo', $response->getContent());
    }

    public function testMorphingArray()
    {
        $messages = new MessageBag(['foo' => 'bar']);

        $response = (new Response(['foo' => 'bar', 'baz' => $messages]))->morph();

        $this->assertEquals('foo({"foo":"bar","baz":{"foo":["bar"]}});', $response->getContent());
    }

    public function testMorphingUnknownType()
    {
        $this->assertEquals(1, (new Response(1))->morph()->getContent());
    }
}
