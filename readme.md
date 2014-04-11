# API for Laravel

This is an API package for the Laravel framework. It allows you to build a flexible RESTful API that can be consumed externally and by your own application.

[![Build Status](https://travis-ci.org/dingo/api.svg?branch=master)](https://travis-ci.org/dingo/api)

## Installation

The package can be installed with Composer, either by modifying your `composer.json` directly or using the `composer require` command.

```
composer require dingo/api:dev-master
```

> Note that this package is still under development and has not been tagged as stable.

Once the package is installed you'll need to add it to your providers in `app/config/app.php`:

```
"Dingo\Api\ApiServiceProvider"
```

If you're using [OAuth 2.0](https://github.com/dingo/oauth2-server-laravel) to authenticate requests then you'll also need to add the `OAuth2ServiceProvider`:

```
"Dingo\OAuth2\OAuth2ServiceProvider"
```

### Optional Aliases

These aliases are optional but do make life easier and provide you with a terser syntax when working with the API:

```
"API"        => "Dingo\Api\Facades\API",
"Controller" => "Dingo\Api\Routing\Controller",
"Response"   => "Dingo\Api\Facades\Response"
```

The `Controller` and `Response` aliases give you easier access to certain methods which are covered in more detail later.

### Publishing Configuration

You can publish the packages configuration and modify it to better suit your application:

```
php artisan config:publish dingo/api
```

The configuration files will be published to `app/config/packages/dingo/api`.

## Usage

### Defining Routes

Before routes are defined they need to be wrapped in an API group. This group lets the API know, among other things, the version of the API that these routes will respond to.

```php
Route::api(['version' => 'v1'], function()
{
	// Route definitions.
});
```

> API versions are always defined using the `v<versionNumber>` syntax.

Inside the API group you can use the usual route definitions, `Route::get`, `Route::controller`, `Route::resource`, etc.

```php
Route::api(['version' => 'v1'], function()
{
	Route::get('users', function()
	{
		return User::all();
	});
});
```

Just remember that the first matched route is the route that will be served first. Ordering your routes correctly is *extremely* important.

### Prefixes and Subdomains

An API is normally served from a subdomain or using a prefix. API groups accept the same `domain` and `prefix` keys as regular route groups.

If we want our API to be accessible at `example.com/api` we'd use a prefix.

```php
Route::api(['version' => 'v1', 'prefix' => 'api'], function()
{
	// Route definitions.
});
```

If we want our API to be accessible at `api.example.com` we'd use a subdomain.

```php
Route::api(['version' => 'v1', 'domain' => 'api.example.com'], function()
{
	// Route definitions.
});
```

### Responses

When returning data from your API you'll want it to be formatted so that consumers can easily read and understand it. Simply returning an array or an Eloquent object will convert it its JSON representation.

```php
Route::api(['version' => 'v1', 'prefix' => 'api'], function()
{
	Route::get('users', function()
	{
		return User::all();
	});
});
```

When you hit the `example.com/api/users` endpoint the API will automatically convert the Eloquent collection into its JSON representation.

```json
{
	"users": [
		{
			"id": 1,
			"name": "Jason",
			"location": "Australia"
		},
		{
			"id": 2,
			"name": "Dayle",
			"location": "Wales"
		},
		{
			"id": 3,
			"name": "Shawn",
			"location": "The Netherlands"
		}
	]
}
```

### Errors

When creating or updating records with your API you'll often need to return errors when something goes wrong. All error handling should be done via
exceptions. The following exceptions can and should be thrown when you encounter an error that you need to alert the consumer of.

```
Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
Symfony\Component\HttpKernel\Exception\BadRequestHttpException
Symfony\Component\HttpKernel\Exception\ConflictHttpException
Symfony\Component\HttpKernel\Exception\GoneHttpException
Symfony\Component\HttpKernel\Exception\HttpException
Symfony\Component\HttpKernel\Exception\LengthRequiredHttpException
Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
Symfony\Component\HttpKernel\Exception\NotFoundHttpException
Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException
Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException
Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException
Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException
Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException
```

You may have noticed these are all Symfony exceptions and they extend from Symfony's `HttpException`.

As an example you might throw a `ConflictHttpException` when you attempt to update a record that has since been updated prior to this request.

```php
Route::api(['version' => 'v1', 'prefix' => 'api'], function()
{
	Route::put('user/{id}', function($id)
	{
		$user = User::find($id);

		if ($user->updated_at > Input::get('last_updated'))
		{
			throw new Symfony\Component\HttpKernel\Exception\ConflictHttpException('User was updated prior to your request.');
		}

		// Update user as per usual.
	});
});
```

The API will automatically catch thrown exceptions and convert them into a JSON representation as well as adjusting the HTTP status code. The above exception would have the following JSON representation.

```json
{
	"message": "User was updated prior to your request."
}
```

The API also provides a couple of its own exceptions which you may throw when dealing with resources.

```
Dingo\Api\Exception\DeleteResourceFailedException
Dingo\Api\Exception\ResourceException
Dingo\Api\Exception\StoreResourceFailedException
Dingo\Api\Exception\UpdateResourceFailedException
```

These exceptions are a little special in that they allow you to pass along any validation errors that occured when trying to create, update, or delete resources. As an example you might throw a `StoreResourceFailedException` when you encounter errors when trying to validate the creation of a new user:

```php
Route::api(['version' => 'v1', 'prefix' => 'api'], function()
{
	Route::post('users', function()
	{
		$rules = [
			'username' => ['required', 'alpha'],
			'password' => ['required', 'min:7']
		];

		$payload = Input::only('username', 'password');

		$validator = Validator::make($payload, $rules);

		if ($validator->fails())
		{
			throw new Dingo\Api\Exception\StoreResourceFailedException('Could not create new user.', $validator->errors());
		}

		// Create user as per usual.
	});
});
```

The API would catch this exception and return it as a JSON response with the HTTP status code 422. This response would have the following JSON representation.

```json
{
	"message": "Could not create new user.",
	"errors": {
		"username": [
			"A username is required."
		],
		"password": [
			"A password must have at least 7 characters."
		]
	}
}
```

### Authentication and Authorization

The API comes with built in authentication. Authentication can be enabled for entire API groups or for a specific endpoint. By default `basic` authentication is enabled, this can be modified in the `app/config/packages/dingo/api/config.php` configuration file once you've published it.

> You can enable `oauth2` authentication however you must require [`dingo/oauth2-server`](https://github.com/dingo/oauth2-server).

The flow for authenticating requests is as follows:

	         Client Requests API
	                 ↓
	 API Determines If Route Is Protected
	                 ↓
	 API Determines Type Of Authentication
	                 ↓
	  API Attempts To Authenticate Request
	                 ↓
	API Throws Error If Authentication Fails
	          And Denies Access
	                 ↓
	   If Successful Access Is Granted

#### Protecting Routes

Protection can be enabled for all routes within a group.

```php
Route::api(['version' => 'v1', 'protected' => true], function()
{
	// Route definitions.
});
```

Or it can be enabled for only a specific route.

```php
Route::api(['version' => 'v1'], function()
{
	Route::get('users', ['protected' => true, function()
	{
		return User::all();
	}]);
});
```

Or it can be disabled for specific routes.

```php
Route::api(['version' => 'v1', 'protected' => true], function()
{
	Route::get('users', ['protected' => false, function()
	{
		return User::all();
	}]);
});
```

When using controllers you can use the `protect` and `unprotect` methods from within your constructor.

```php
class UsersController extends Controller {

	public function __construct()
	{
		$this->unprotect('index');
	}

}
```

> Protecting or unprotecting methods in controllers is only available if your controllers are extending `Dingo\Api\Routing\Controller`.

#### Authenticated User

Once inside a protected endpoint you can retrieve the authenticated user.

```php
Route::api(['version' => 'v1', 'prefix' => 'api', 'protected' => true], function()
{
	Route::get('user', function()
	{
		return API::user();
	});
});
```

The `API::user` method returns either an Eloquent model or an instance of `Illuminate\Auth\GenericUser` depending on which driver you have chosen in `app/config/auth.php`.

If you're using controllers you can type-hint `Dingo\Api\Authentication` and the dependency will be automatically resolved by Laravel's IoC container.

```php
class UserController extends Controller {

	protected $auth;

	public function __construct(Dingo\Api\Authentication $auth)
	{
		$this->auth = $auth;

		$this->protect('index');
	}

	public function index()
	{
		return $this->auth->user();
	}

}
```

### OAuth 2.0

This package makes use of the [dingo/oauth2-server](https://github.com/dingo/oauth2-server) package. Please refer to that package on how to setup and configure your OAuth 2.0 server.

#### OAuth 2.0 Scopes

To have finer control over the protected routes you should be using scopes. Access tokens can be issued with certain scopes that allow a client to make requests to API endpoints that might otherwise be unaccessible.

Scopes can be set for an entire API group:

```php
Route::api(['version' => 'v1', 'prefix' => 'api', 'protected' => true, 'scopes' => 'read_user_data'], function()
{
	// All endpoints are now protected and only access tokens with the "read_user_data" scope will be give access.
});
```

Or for a specific route:

```php
Route::api(['version' => 'v1', 'prefix' => 'api', 'protected' => true], function()
{
	Route::get('user', ['scopes' => 'read_user_data', function()
	{
		// This endpoint will only allow access tokens with the "read_user_data" scope.
	}]);
});
```

Scopes can also be set from within a controllers constructor:

```php
public function __construct()
{
	$this->scope('read_user_data', 'index');
}
```

If the method name is missing the scope will be applied to all methods on the controller. You can also pass an array of scopes to any of the above methods.

It's also easy to customize the response of a particular endpoint based on the scopes the access token has.

```php
Route::api(['version' => 'v1', 'prefix' => 'api', 'protected' => true], function()
{
	Route::get('user/{id}', function($id)
	{
		$user = User::find($id);

		$hidden = ['password'];

		if ( ! API::token()->hasScope('read_users_age'))
		{
			$hidden[] = 'age';
		}

		if ( ! API::token()->hasScope('read_users_email'))
		{
			$hidden[] = 'email';
		}

		$user->setHidden($hidden);

		return $user;
	});
});
```

Now only an access tokens with the `read_users_age` scope can see the users age and access tokens with the `read_users_email` can see the users e-mail. An access token with both scopes will be able to see all the users data.

#### Issuing OAuth 2.0 Access Tokens

OAuth 2.0 access tokens can be issued from an unprotected `POST` endpoint on your API:

```php
Route::api(['version' => 'v1', 'prefix' => 'api'], function()
{
	Route::post('token', ['protected' => false, function()
	{
		$payload = Input::only('grant_type', 'client_id', 'client_secret', 'username', 'password', 'scope');

		return API::issueToken($payload);
	}]);
});
```

### Internal Requests

Having your application consume your own API is extremely useful and has a number of benefits:

1. You can build your application on top of a solid API.
2. The API will return the actual data and not its JSON representation.
3. You can catch thrown exceptions and deal with errors.

Here is how you'd send an internal request to get the Eloquent collection of all users.

```php
Route::get('/', function()
{
	$users = API::get('users');

	return View::make('index')->with('users', $users);
});
```

> You don't need to include the API prefix or domain when internally calling an API endpoint.

#### Internal Request With Parameters

```php
API::with(['name' => 'Jason', 'location' => 'Australia'])->post('users');

API::post('users', ['name' => 'Jason', 'location' => 'Australia']);
```

#### Targetting A Specific Version

```php
API::version('v2')->get('users');
```

#### Pretending To Be A User

When performing internal requests to your API you might want to access a protected endpoint which makes use of the `API::user` method. You can easily tell the API to *be* a given user.

```php
Route::get('user/{id}/posts', function($id)
{
	$user = User::find($id);

	$posts = API::be($user)->get('user/posts');

	return View::make('user.posts')->with('posts', $posts);
});
```

The protected API route might look like this (excluded the API group for simplicity):

```php
Route::api(['version' => 'v1', 'protected' => true], function()
{
	Route::get('user/posts', function()
	{
		return API::user()->posts;
	});
});
```

Because the API route is protected we are able to safely use `API::user` to fetch this users posts. Our regular route can internally call this API route and act on behalf of the user.

### Automatic Dependency Resolution for Dispatcher and Authentication

When using controllers you can type-hint both `Dingo\Api\Dispatcher` and `Dingo\Api\Authentication` to have the dependencies automatically resolved by Laravel's IoC container. If you swapped out the `Controller` facade in `app/config/app.php` with `Dingo\Api\Routing\Controller` then this is already done for you.

> You'll need to type-hint them yourself if you overload the constructor.

The `Dispatcher` instances allow you to internally dispatch API requests.

```php
public function getUsers()
{
	$users = $this->api->get('users');

	return View::make('users.all')->with('users', $users);
}

And the `Authentication` instances allows you to interact with the authenticated user inside a protected endpoint.

```php
public function index()
{
	return $this->auth->user();
}
```