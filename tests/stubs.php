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

class InternalControllerActionRoutingStub extends Illuminate\Routing\Controller {

	public function index()
	{
		return 'foo';
	}

}

class AuthorizationProviderStub extends Dingo\Api\Auth\AuthorizationProvider {

	public function authenticate(Illuminate\Http\Request $request, Illuminate\Routing\Route $route) {}

	public function getAuthorizationMethod() { return 'foo'; }

}

class CustomProviderStub extends Dingo\Api\Auth\Provider {

	public function authenticate(Illuminate\Http\Request $request, Illuminate\Routing\Route $route)
	{
		return 1;
	}

}

class FooTransformerStub extends League\Fractal\TransformerAbstract {

	public function transform(Foo $foo)
	{
		return ['foo' => 'bar'];
	}

}

class BarTransformerStub extends League\Fractal\TransformerAbstract {

	protected $availableIncludes = ['foo'];

	public function transform(Bar $bar)
	{
		return ['bar' => 'baz'];
	}

	public function includeFoo(Bar $bar)
	{
		return $this->item(new Foo, new FooTransformerStub);
	}

}

class Foo {

}

class Bar implements Dingo\Api\Transformer\TransformableInterface  {
	
	public function getTransformer()
	{
		return new BarTransformerStub;
	}
	
}
