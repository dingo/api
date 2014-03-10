<?php

class HttpResponseTest extends PHPUnit_Framework_TestCase {


	public function testMorphingJsonableInterface()
	{
		$messages = new Illuminate\Support\MessageBag(['foo' => 'bar']);

		$response = with(new Dingo\Api\Http\Response($messages))->morph();

		$this->assertEquals('{"foo":["bar"]}', $response->getContent());
	}


	public function testMorphingString()
	{
		$response = with(new Dingo\Api\Http\Response('foo'))->morph();

		$this->assertEquals('{"message":"foo"}', $response->getContent());
	}


	public function testMorphingArrayableInterface()
	{
		$messages = new Illuminate\Support\MessageBag(['foo' => 'bar']);

		$response = with(new Dingo\Api\Http\Response(['foo' => 'bar', 'baz' => $messages]))->morph();

		$this->assertEquals('{"foo":"bar","baz":{"foo":["bar"]}}', $response->getContent());
	}


}