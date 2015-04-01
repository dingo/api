### v0.8.*@dev (master)

### v0.8.3

##### General

- Updated Tymon's JWTAuth to the latest version, `0.4.1`.

##### Added

- Can now use `RateLimiter::setRateLimiter` to change the key used to rate limit requests from the clients IP.

##### Fixed

- Made the trace readable by exploding on the EOL character.
- API filters are now applied before any other filters to ensure they are run first.
- Request and response instances are correctly set on the formatter for exception responses.
- Scopes set on groups are now parsed correctly.
- Routes are now added to the correct groups and matched correctly.
- OPTION requests are now handled correctly for API routes so preflights will no longer fail.
- Eager loading for Fractal transformers now checks the available includes before attempting to load.
- Raw internal requests now have the content run through any transformers as was initially described when implemented.

##### Changed

- `ResponseFactory::collection` is now type-hinted to `Illuminate\Support\Collection`.
- Refined the handling of route matching.

### v0.8.2

##### Deprecated

- `Dingo\Api\Transformer\TransformableInterface` is now deprecated. It's recommended you use the Response Builder.

##### Fixed

- Fixed bug where some server variables were not being set properly.
- Fixed inconsistent response when returning an empty collection that is meant to be transformed.
- Fixed potential HHVM bug caused by `json_encode` being run on certain elements. Debug mode now returns trace as a string.

### v0.8.1

##### Added

- `Symfony\Component\HttpKernel\Exception\BadRequestHttpException` is now thrown when invalid API version is requested.
- Responses with HTTP error status codes will now throw a `Dingo\Api\Exception\InternalHttpException` when requested internally.

##### Fixed

- Fixed bug where using the `Input` or `Request` facade prior to internal requests resulted in unexpected input behaviour.
- Fixed recursive call to the `ControllerTrait::__call` method.

### v0.8.0

##### Added

- Can now add cookies when using the response builder.
- Dispatcher can now internally send cookies.

##### Removed

- Removed `ResponseBuilder::addHeaders` and `ResponseBuilder::headers`.

##### Changed

- Changed `ResponseBuilder::addHeader` to `ResponseBuilder::withHeader`.

##### Fixed

- Fixed bug where headers were not completely copied when creating a new response from an existing response.
- Fixed bug where request was not correctly rebound to the container.

### v0.7.3

##### Removed

- Default throttles for rate limiting have been removed.

##### Fixed

- Fixed bug where any response object that could not be type cast as a string would throw an `UnexpectedValueException`.
- Fixed bug where request was not bound as an instance resulting in rebounds not being fired.

### v0.7.2

##### Fixed

- Fixed inconsistent response when returning an empty paginator and using transformers.
- Fixed bug with protected endpoints becoming unprotected when setting protection on both group and route.
- Fixed bug where routes defined before the replacement of the bound router would be lost.
- `AuthFilter` now require authentication for internal requests.
- Don't catch exceptions and handle them within the `AuthFilter`, this causes issues with internal requests.

##### Added

- Added a `debug` configuration key which enables/disables a more detailed error response for some exceptions.

### v0.7.1

##### Fixed

- Fixed bug with Basic authentication.

### v0.7.0

##### General

- `Dingo\Api\ApiServiceProvider` is now at `Dingo\Api\Provider\ApiServiceProvider`. It has been split up into several smaller service providers.
- Made namespaces more consistent by making them all singular.
- Custom transformation layers must now implement `Dingo\Api\Transformer\TransformerInterface`.
- Authentication providers must now implement the `Dingo\Api\Auth\ProviderInterface`.
- Upgraded the League OAuth 2.0 package to version 4.
- Renamed `ControllerTrait::scope` to `ControllerTrait::scopes` for consistency with other methods of definding scopes.

##### Added

- Some dependencies are now injected into controller via setters instead of in the constructor. ([#111](https://github.com/dingo/api/issues/111))
- Authentication and Rate Limiting are now filters. Both can be changed on a per-route basis.
- Rate limiting now makes use of different throttles so the configuration has changed slightly.
- Added `API::response()` to make a new response builder from closure based routes.
- Added `LeagueOAuth2Provider::setUserResolver` and `LeagueOAuth2Provider::setClientResolver`.
- `Dingo\Api\Routing\Controller` is now `Dingo\Api\Routing\ControllerTrait`, you can now create your own base controller and simply use the trait.
- Allow raw data to be sent with an internal request.
- Allow custom headers to be set on internal requests.
- Added a `Dispatcher::json` method to easily send raw JSON data with an internal request.
- `Dispatcher::attach` now accepts either an array of file details or an array of `UploadedFile` instances.
- Added a `Dispatcher::on` method to indicate which domain the dispatcher should work on.
- Relations are automatically loaded when using Fractal includes.
- Added a `Dispatcher::raw` method to return a raw response object instead of the original content.

##### Removed

- Removed `Response::api()` macro.
- Removed `LeagueOAuth2Provider::setUserCallback` and `LeagueOAuth2Provider::setClientCallback`.
- Removed `ResponseFormat::formatOther` as non-formattable responses are returned as they are.

##### Fixed

- Global router "after" filter is now fired even when an exception is thrown and caught by the API.
- The response builder will now return the original response instance to internal requests and is decoupled from Fractal.
- Errors generated by the response factory and the exception handler are now the same.
- `LeagueOAuth2Provider` will now authenticate for any matching scopes instead of requiring all scopes.

### v0.6.5

##### Fixed

- Fixed bug where conditional requests affected routes outside of the API. ([#141](https://github.com/dingo/api/issues/141))

### v0.6.4

##### Fixed

- Fixed incompatibility bug with the latest Laravel. ([#156](https://github.com/dingo/api/issues/156))

### v0.6.3

##### Added

- Basic implementation of attaching files for internal requests.

### v0.6.2

##### Added

- Added `addMeta` (and `meta` alias) to the `ResponseBuilder` to add Fractal meta data.

### v0.6.1

##### Fixed

- Fixed bug where the request was not being set on the transformer prior to dispatching the request.  ([#101](https://github.com/dingo/api/issues/101))

### v0.6.0

##### General

- Refactored transformers. You can still use the `API::transform` method, however if you're using the underlying transformer instance to register your bindings you should now use `register`, e.g.,  `$app['dingo.api.transformer']->register('Class', 'ClassTransformer');`.
- Can now grab the underlying Fractal instance, useful for custom serializers.
- Converted codebase to PSR-2.
- Authentication has been completely overhauled.

##### Added

- Added the [`ResponseBuilder`](https://github.com/dingo/api/wiki/Responses#response-builder).
- Conditional requests can now be enabled/disabled globally or on groups and individual endpoints.

##### Fixed

- Non-morphable responses such as those created from `Response::download` can now be returned.
- Fixed bug where headers set on a thrown exception were not properly set on the generated response.

### v0.5.0

##### General

- Updated to the latest version of Fractal.

##### Added

- Added a `artisan api:routes` command.
- Allow non-HTTP exceptions to be handled with `API::error` for better exception handling.

### v0.4.1

##### Fixed

- Properly check collections are transformable when bound by contract. ([#34](https://github.com/dingo/api/pull/37))

### v0.4.0

##### Added

- Transformation layer is now configurable meaning you can swap out Fractal for some other transformation library.
- Transformable classes can now implement `Dingo\Api\Transformer\TransformableInterface` to return their respective transformer.

### v0.3.1

##### Fixed

- Fixed bug where collections were not detected as being a transformable type. ([#30](https://github.com/dingo/api/issues/30))

### v0.3.0

##### General

- Change the `embeds` configuration key to `fractal_includes`.

##### Fixed

- Only resolve controllers when they are being used. This prevents controllers from being resolved for no particular reason and should be more efficient when using large number of controllers.

### v0.2.4

##### General

- Configuration option for the default response format.

##### Fixed

- Properly queue and replace requests and route instances.

### v0.2.3

##### General

- Default response format can now be configured in the configuration file.
- Custom authentication providers that extend at runtime can be registered as instances.
- Custom authentication providers that extend at runtime now get the application container as a parameter when using an anonymous function to resolve the provider.
- Minor internal changes to authorization based authentication providers.

### v0.2.3

##### Fixed

- Properly maintain request and route stacks.

### v0.2.2

##### Fixed

- Fixed bug where transformers would try to transform a non-transformable response.

### v0.2.1

##### Added

- Added Fractal pagination support.
- Allow authentication providers to be extended at runtime.

### v0.2.0

##### General

- Refactoring and simplifying of authentication layer.

##### Added

- Added Fractal transformers.

### v0.1.2

##### Fixed

- Router gracefully falls back to default API version if no exact matching collection is found.

### v0.1.1

##### Fixed

- APIs can now be versioned with point releases, e.g., `v1.1`.
- Fixed bug where collections were prematurely matched resulting in 404s when the prematurely matched collection did not contain the correct routes. ([#16](https://github.com/dingo/api/issues/16))

### v0.1.0

- Initial release
