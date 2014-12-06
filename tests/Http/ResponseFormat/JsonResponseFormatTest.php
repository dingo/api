<?php

namespace Dingo\Api\Tests\Http\ResponseFormat;

use Mockery;
use Dingo\Api\Http\Response;
use PHPUnit_Framework_TestCase;
use Illuminate\Support\MessageBag;
use Dingo\Api\Tests\Stubs\EloquentModelStub;
use Illuminate\Database\Eloquent\Collection;
use Dingo\Api\Http\ResponseFormat\JsonResponseFormat;

class JsonResponseFormatTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Response::setFormatters(['json' => new JsonResponseFormat]);
    }

    public function tearDown()
    {
        Mockery::close();

        EloquentModelStub::$snakeAttributes = true;

        Response::setFormatters([]);
    }

    public function testMorphingEloquentModel()
    {
        $response = (new Response(new EloquentModelStub))->morph();

        $this->assertEquals('{"app_user":{"foo":"bar"}}', $response->getContent());
    }

    public function testMorphingEloquentCollection()
    {
        $response = (new Response(new Collection([new EloquentModelStub, new EloquentModelStub])))->morph();

        $this->assertEquals('{"app_users":[{"foo":"bar"},{"foo":"bar"}]}', $response->getContent());
    }

    public function testMorphingEmptyEloquentCollection()
    {
        $response = (new Response(new Collection))->morph();

        $this->assertEquals('[]', $response->getContent());
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

        $this->assertEquals('{"foo":"bar","baz":{"foo":["bar"]}}', $response->getContent());
    }

    public function testMorphingUnknownType()
    {
        $this->assertEquals(1, (new Response(1))->morph()->getContent());
    }

    public function testMorphingEloquentModelWithCamelCasing()
    {
        EloquentModelStub::$snakeAttributes = false;

        $response = (new Response(new EloquentModelStub))->morph();

        $this->assertEquals('{"appUser":{"foo":"bar"}}', $response->getContent());
    }

    public function testMorphingEloquentCollectionWithCamelCasing()
    {
        EloquentModelStub::$snakeAttributes = false;

        $response = (new Response(new Collection([new EloquentModelStub, new EloquentModelStub])))->morph();

        $this->assertEquals('{"appUsers":[{"foo":"bar"},{"foo":"bar"}]}', $response->getContent());
    }
}
