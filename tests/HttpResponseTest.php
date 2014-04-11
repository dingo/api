<?php

class HttpResponseTest extends PHPUnit_Framework_TestCase {


	public function testMorphingEloquentModel()
	{
		$response = with(new Dingo\Api\Http\Response(new UserEloquentModelStub))->morph();

		$this->assertEquals('{"user":{"foo":"bar"}}', $response->getContent());
	}


	public function testMorphingEloquentCollection()
	{
		$collection = new Illuminate\Database\Eloquent\Collection;
		$collection->push(new UserEloquentModelStub);
		$collection->push(new UserEloquentModelStub);

		$response = with(new Dingo\Api\Http\Response($collection))->morph();

		$this->assertEquals('{"users":[{"foo":"bar"},{"foo":"bar"}]}', $response->getContent());
	}


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

class UserEloquentModelStub extends Illuminate\Database\Eloquent\Model {

	protected $table = 'user';

	public function toArray()
	{
		return ['foo' => 'bar'];
	}

}