<?php

namespace Dingo\Api\Tests\Http\Response\Format;

use Mockery;
use Dingo\Api\Http\Response;
use Illuminate\Http\Request;
use PHPUnit_Framework_TestCase;
use Illuminate\Support\MessageBag;
use Dingo\Api\Http\Response\Format\Jsonp;
use Dingo\Api\Tests\Stubs\EloquentModelStub;
use Illuminate\Database\Eloquent\Collection;

class JsonpTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $formatter = new Jsonp;
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
        $response = (new Response(new EloquentModelStub))->morph();

        $this->assertEquals('foo({"foo_bar":{"foo":"bar"}});', $response->getContent());
    }

    public function testMorphingEloquentCollection()
    {
        $response = (new Response(new Collection([new EloquentModelStub, new EloquentModelStub])))->morph();

        $this->assertEquals('foo({"foo_bars":[{"foo":"bar"},{"foo":"bar"}]});', $response->getContent());
    }

    public function testMorphingEmptyEloquentCollection()
    {
        $response = (new Response(new Collection))->morph();

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
