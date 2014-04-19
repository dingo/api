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

class EmptyEloquentCollectionStub extends Illuminate\Database\Eloquent\Collection {

}

class JsonableStub implements Illuminate\Support\Contracts\JsonableInterface {

	public function toJson($options = 0)
	{
		return json_encode(['foo' => 'bar']);
	}

}

class WildcardScopeControllerStub extends Dingo\Api\Routing\Controller {

	public function __construct()
	{
		$this->scope(['foo', 'bar']);
	}

	public function index() {}

}

class IndividualScopeControllerStub extends Dingo\Api\Routing\Controller {

	public function __construct()
	{
		$this->scope(['foo', 'bar'], 'index');
	}

	public function index() {}

}

class ProtectedControllerStub extends Dingo\Api\Routing\Controller {

	public function __construct()
	{
		$this->protect('index');
	}

	public function index() {}

}

class UnprotectedControllerStub extends Dingo\Api\Routing\Controller {

	public function __construct()
	{
		$this->unprotect('index');
	}

	public function index() {}

}

class StubHttpException extends Symfony\Component\HttpKernel\Exception\HttpException {

}