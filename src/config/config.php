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
    | Default API Prefix
    |--------------------------------------------------------------------------
    |
    | A default prefix to use for your API routes so you don't have to
    | specify it for each group.
    |
    */

    'prefix' => null,

    /*
    |--------------------------------------------------------------------------
    | Default API Domain
    |--------------------------------------------------------------------------
    |
    | A default domain to use for your API routes so you don't have to
    | specify it for each group.
    |
    */

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Conditional Requests
    |--------------------------------------------------------------------------
    |
    | Globally enable conditional requests so that an ETag header is added to
    | any successful response. Subsequent requests will perform a check and
    | will return a 304 Not Modified. This can also be enabled or disabled
    | on certain groups or routes.
    |
    */

    'conditional_request' => true,

    /*
    |--------------------------------------------------------------------------
    | Authentication Providers
    |--------------------------------------------------------------------------
    |
    | The authentication providers that should be used when attempting to
    | authenticate an incoming API request.
    |
    */

    'auth' => [
        'basic' => function ($app) {
            return new Dingo\Api\Auth\BasicProvider($app['auth']);
        }
    ],

    /*
    |--------------------------------------------------------------------------
    | Throttling / Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Consumers of your API can be limited to the amount of requests they can
    | make. You can create your own throttles or simply change the default
    | throttles. To disable rate limiting simply remove all throttles.
    |
    */

    'throttling' => [
        'authenticated'   => new Dingo\Api\Http\RateLimit\AuthenticatedThrottle(['limit' => 1000, 'expires' => 60]),
        'unauthenticated' => new Dingo\Api\Http\RateLimit\UnauthenticatedThrottle(['limit' => 100, 'expires'  => 60])
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Transformer
    |--------------------------------------------------------------------------
    |
    | Responses can be transformed so that they are easier to format. By
    | default a Fractal transformer will be used to transform any
    | responses prior to formatting. You can easily replace
    | this with your own transformer.
    |
    */

    'transformer' => 'Dingo\Api\Transformer\FractalTransformer',

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

    'default_format' => 'json',

    'formats' => [

        'json' => 'Dingo\Api\Http\ResponseFormat\JsonResponseFormat'

    ]

];
