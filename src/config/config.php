<?php

return [

	/*
	|--------------------------------------------------------------------------
	| API Vendor
	|--------------------------------------------------------------------------
	|
	| Your vendor is used in the "Accept" request header and will be used by
	| the consumers of your API. Typically this will be the name of your
	| application or website.
	|
	*/

	'vendor' => '',

	/*
	|--------------------------------------------------------------------------
	| Default API Version
	|--------------------------------------------------------------------------
	|
	| When a request is made to the API and no version is specified then it
	| will default to the version specified here.
	|
	*/

	'version' => 'v1',

	/*
	|--------------------------------------------------------------------------
	| Authentication Providers
	|--------------------------------------------------------------------------
	|
	| You can attempt to authenticate requests using different providers.
	| Available providers: "basic", "oauth"
	|
	*/

	'auth' => [
		'basic',
		'oauth'
	]

];