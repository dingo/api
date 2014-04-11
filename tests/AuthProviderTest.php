<?php

class AuthProviderTest extends PHPUnit_Framework_TestCase {


	/**
	 * @expectedException \Exception
	 */
	public function testValidatingAuthorizationHeaderFailsWhenInvalidAndThrowsException()
	{
		$provider = new ProviderStub;
		$request = Illuminate\Http\Request::create('/', 'GET');
		$request->headers->set('authorization', 'bar');

		$provider->validateAuthorizationHeader($request);
	}


	public function testValidatingAuthorizationHeaderSucceedsAndReturnsNull()
	{
		$provider = new ProviderStub;
		$request = Illuminate\Http\Request::create('/', 'GET');
		$request->headers->set('authorization', 'foo');

		$this->assertNull($provider->validateAuthorizationHeader($request));
	}


}

class ProviderStub extends Dingo\Api\Auth\Provider {

	public function authenticate(array $scopes) {}

	public function getAuthorizationMethod() { return 'foo'; }

}