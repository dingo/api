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
	| The authentication providers that should be used when attempting to
	| authenticate an incoming API request.
	|
	| Available: "basic", "oauth2"
	|
	*/

	'auth' => ['basic'],

	/*
	|--------------------------------------------------------------------------
	| Response Formats
	|--------------------------------------------------------------------------
	|
	| Responses can be returned in multiple formats by registering different
	| response formatters. You can also customize an existing response
	| formatter.
	|
	*/

	'formats' => [
		'json' => new Dingo\Api\Http\ResponseFormat\JsonResponseFormat
	]

];