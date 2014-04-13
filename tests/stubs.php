<?php


class EloquentModelStub extends Illuminate\Database\Eloquent\Model {

	protected $table = 'user';

	public function toArray()
	{
		return ['foo' => 'bar'];
	}

}

class EloquentCollectionStub extends Illuminate\Database\Eloquent\Collection {

	public function __construct()
	{
		$this->items = [
			new EloquentModelStub,
			new EloquentModelStub
		];
	}

}

class JsonableStub implements Illuminate\Support\Contracts\JsonableInterface {

	public function toJson($options = 0)
	{
		return json_encode(['foo' => 'bar']);
	}

}