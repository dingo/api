### v0.7.*@dev (develop)

- Refactoring of a lot of the core.
- `Dingo\Api\ApiServiceProvider` is now at `Dingo\Api\Providers\ApiServiceProvider`. It has been split up into several smaller service providers.
- Some dependencies are now injected into controller via setters instead of in the constructor. ([#111](https://github.com/dingo/api/issues/111))
- Global router "after" filter is now fired even when an exception is thrown and caught by the API.
- Authentication and Rate Limiting are now filters. Both can be changed on a per-route basis.
- Rate limiting now makes use of different throttles so the configuration has changed slightly.
- The response builder will now return the original response instance to internal requests and is decoupled from Fractal.
- Removed `Response::api()` macro, you can now use `API::response()` to make a new response builder from closure based routes.

### v0.6.5

- Fixed bug where conditional requests affected routes outside of the API. ([#141](https://github.com/dingo/api/issues/141))

### v0.6.4

- Fixed incompatibility bug with the latest Laravel. ([#156](https://github.com/dingo/api/issues/156))

### v0.6.3

- Basic implementation of attaching files for internal requests.
- Minor bug fixes.

### v0.6.2

- Added `addMeta` (and `meta` alias) to the `ResponseBuilder` to add Fractal meta data.

### v0.6.1

- Fixed bug where the request was not being set on the transformer prior to dispatching the request.  ([#101](https://github.com/dingo/api/issues/101))

### v0.6.0

- Refactored transformers. You can still use the `API::transform` method, however if you're using the underlying transformer instance to register your bindings you should now use `register`, e.g.,  `$app['dingo.api.transformer']->register('Class', 'ClassTransformer');`.
- Can now grab the underlying Fractal instance, useful for custom serializers, `$app['dingo.api.transformer']->getFractal();`.
- Implememnted the [`ResponseBuilder`](https://github.com/dingo/api/wiki/Responses#response-builder).
- Converted codebase to PSR-2.
- Conditional requests can now be enabled/disabled globally or on groups and individual endpoints.
- Big refactor on authentication. If you were using OAuth 2.0 then you'll need to update your configuration file. You need to set at least one but possibly two callbacks in your OAuth config.
  ```php
  'auth' => [
      'oauth' => function($app) {
          $provider = new Dingo\Api\Auth\LeagueOAuth2Provider($app['oauth2.resource-server']);

          $provider->setUserCallback(function($id) {
              return User::find($id);
          });

          $provider->setClientCallback(function($id) {
              return Client::find($id);
          });

          return $provider;
      }
  ]
```
  You'll need to adjust these callbacks accordingly. They are used when you call `API::user` from within a protected endpoint.
- Non-morphable responses such as those created from `Response::download` can now be returned.
- Headers set on a thrown exception are not properly set on the generated response.
- Numerous bug fixes.

### v0.5.0

- Implemented `artisan api:routes` command.
- Allow non-HTTP exceptions to be handled with `API::error` for better exception handling.
- Updated to the latest version of Fractal.

### v0.4.1

- Properly check collections are transformable when bound by contract. ([#34](https://github.com/dingo/api/pull/37))

### v0.4.0

- Transformation layer is now configurable meaning you can swap out Fractal for some other transformation library.
- Transformable classes can now implement `Dingo\Api\Transformer\TransformableInterface` to return their respective transformer.

##### Upgrading To v0.4.0 From Earlier Versions

If upgrading from earlier versions you should change your published configuration file. The `fractal_embeds` key has been removed and replaced with a [`transformer`](https://github.com/dingo/api/blob/8064af1211c470703d650b42789e05ec7c7294d7/src/config/config.php#L116) key.

### v0.3.1

- Fixed bug where collections were not detected as being a transformable type. ([#30](https://github.com/dingo/api/issues/30))

### v0.3.0

- Only resolve controllers when they are being used. This prevents controllers from being resolved for no particular reason and should be more efficient when using large number of controllers.
- Change the `embeds` configuration key to `fractal_includes`.

##### Upgrading To v0.3.0 From Earlier Versions

If upgrading from earlier versions you should change your published configuration file (if any) so that the `embeds` key is `fractal_includes`.

### v0.2.4

- Properly queue and replace requests and route instances.
- Configuration option for the default response format.

### v0.2.3

- Default response format can now be configured in the configuration file.
- Custom authentication providers that extend at runtime can be registered as instances.
- Custom authentication providers that extend at runtime now get the application container as a parameter when using an anonymous function to resolve the provider.
- Minor internal changes to authorization based authentication providers.

### v0.2.3

- Properly maintain request and route stacks.

### v0.2.2

- Fixed bug where transformers would try to transform a non-transformable response.

### v0.2.1

- Implemented Fractal pagination support.
- Allow authentication providers to be extended at runtime.
- Minor bug fixes.

### v0.2.0

- Implemented Fractal transformers.
- Refactoring and simplifying of authentication layer.
- Minor bug fixes.

##### Upgrading To v0.2.0 From Earlier Versions

If upgrading from earlier versions you should be aware of numerous configuration file changes. Either refer to the updated configuration file or re-publish your configuration file. The most obvious change is authentication providers are now defined in the configuration file itself.

### v0.1.2

- Router gracefully falls back to default API version if no exact matching collection is found.

### v0.1.1

- APIs can now be versioned with point releases, e.g., `v1.1`.
- Fixed bug where collections were prematurely matched resulting in 404s when the prematurely matched collection did not contain the correct routes. ([#16](https://github.com/dingo/api/issues/16))

### v0.1.0

- Initial release
