<?php

class AuthAuthorizationProviderTest extends PHPUnit_Framework_TestCase {


	/**
	 * @expectedException \Exception
	 */
	public function testValidatingAuthorizationHeaderFailsWhenInvalidAndThrowsException()
	{
		$provider = new AuthorizationProviderStub;
		$request = Illuminate\Http\Request::create('/', 'GET');
		$request->headers->set('authorization', 'bar');
		$provider->validateAuthorizationHeader($request);
	}


	public function testValidatingAuthorizationHeaderSucceedsAndReturnsNull()
	{
		$provider = new AuthorizationProviderStub;
		$request = Illuminate\Http\Request::create('/', 'GET');
		$request->headers->set('authorization', 'foo');
		$this->assertNull($provider->validateAuthorizationHeader($request));
	}


}
