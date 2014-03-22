# Dingo API

This is an API package for the Laravel framework. It allows you to build a flexible RESTful API that can be consumed externally and by your own application.

## Installation

To install the package add it to your `composer.json`:

	"require": {
		"dingo/api": "1.0.*"
	}

Once the package is installed you'll need to add it to your providers in `app/config/app.php`:

	"Dingo\Api\ApiServiceProvider"

If you're using OAuth 2.0 to authenticate requests then you'll also need to add the `OAuth2ServiceProvider`:

	"Dingo\OAuth2\OAuth2ServiceProvider"

### Optional Aliases

These aliases are optional but do make life easier and provide you with a terser syntax when working with the API.

	"API"        => "Dingo\Api\Facades\API",
	"Controller" => "Dingo\Api\Routing\Controller",
	"Response"   => "Dingo\Api\Facades\Response"

The `Controller` and `Response` aliases give you easier access to certain methods which are covered in more detail later.

## Usage

### Defining Routes

Before routes are defined they need to be wrapped in an API group. This group lets the API know, among other things, the version of the API that these routes will respond to.

	Route::api(['version' => 'v1'], function()
	{
		// Route definitions.
	});

> API versions are always defined using the `v<versionNumber>` syntax.

Inside our group we can define routes as we normally would.

	Route::api(['version' => 'v1'], function()
	{
		Route::get('users', function()
		{
			return User::all();
		});
	});

Just remember that the first matched route is the route that will be served first. Ordering your routes correctly is extremely important.

#### Prefixing and Subdomains

APIs are normally served from a subdomain or using a prefix. API groups accept the same `domain` and `prefix` keys as regular route groups.

If we want our API to be accessible at `example.com/api` we'd use a prefix:

	Route::api(['version' => 'v1', 'prefix' => 'api'], function()
	{
		// Route definitions.
	});

If we want our API to be accessible at `api.example.com` we'd use a subdomain:

	Route::api(['version' => 'v1', 'domain' => 'api.example.com'], function()
	{
		// Route definitions.
	});

#### Protecting Routes

By default your API will be accessible to everyone. Usually this isn't desirable as some endpoints may contain sensitive information or they may even create or update records in a database.

Protection can be enabled for all routes within a group:

	Route::api(['version' => 'v1', 'protection' => true], function()
	{
		// Route definitions.
	});

Or it can be enabled for only a specific route:

	Route::api(['version' => 'v1'], function()
	{
		Route::get('users', ['protection' => true, function()
		{
			return User::all();
		}]);
	});

Or it can be disabled for specific routes:

	Route::api(['version' => 'v1', 'protected' => true], function()
	{
		Route::get('users', ['protection' => false, function()
		{
			return User::all();
		}]);
	});

When using controllers you can use the `protect` and `unprotect` methods from within your constructor:

	class UsersController extends Controller {

		public function __construct()
		{
			$this->unprotect('index');
		}

	}

Or you can fill the `$protected` and `$unprotected` properties:

	class UsersController extends Controller {

		protected $unprotected = ['index'];

	}

> Remember if you're using RESTful controllers then the methods will be `getIndex`, etc.

> Protecting or unprotecting methods in controllers is only available if your controllers are extending `Dingo\Api\Routing\Controller`.

### Responses

When returning data from your API you'll want it to be formatted so that consumers are able to parse it correctly. There is usually no need to return a `Illuminate\Http\Response` object from any of your routes. Simply returning an array or an Eloquent object is a much better approach.

	Route::api(['version' => 'v1', 'prefix' => 'api'], function()
	{
		Route::get('users', function()
		{
			return User::all();
		});
	});

When you hit the `example.com/api/users` endpoint the API will automatically convert the Eloquent collection into its JSON representation:

	[
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

### Errors

When creating or updating records with your API you'll often need to return errors when something goes wrong. All error handling should be done via
exceptions. The following exceptions can and should be thrown when you encounter an error that you need to alert the consumer of.

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

You may have noticed these are all Symfony exceptions and they extend from Symfony's `HttpException`. As an example you might throw a `ConflictHttpException` when you attempt to update a row that has since been updated prior to this request.

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

The API would catch this exception and return it as a response with the HTTP status code 409. This response would be represented by the following JSON:

	{
		"message": "User was updated prior to your request."
	}

The API also provides a couple of its own exceptions which you may throw.

	Dingo\Api\Exception\DeleteResourceFailedException
	Dingo\Api\Exception\ResourceException
	Dingo\Api\Exception\StoreResourceFailedException
	Dingo\Api\Exception\UpdateResourceFailedException

These exceptions allow you to pass along any validation errors that occured when trying to create, update, or delete resources. As an example you might throw a `StoreResourceFailedException` when you encounter validation errors when trying to create a new user.

	Route::api(['version' => 'v1', 'prefix' => 'api'], function()
	{
		Route::post('users', function()
		{
			$rules = [
				'username' => ['required', 'alpha'],
				'password' => ['required', 'min:7']
			];

			$validator = Validator::make(Input::only('username', 'password'), $rules);

			if ($validator->fails())
			{
				throw new Dingo\Api\Exception\StoreResourceFailedException('Could not create new user.', $validator->errors());
			}

			// Create user as per usual.
		});
	});

The API would catch this exception and return it as a response with the HTTP status code 422. This response would be represented by the following JSON:

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

### Internal Requests

Having your application consume your own API is extremely useful and has a number of benefits:

1. You can build your application on top of a solid API.
2. The API will return the actual data and not its JSON representation.
3. You can catch thrown exceptions and deal with errors.

Here is how you'd send an internal request to get the Eloquent collection of all users:

	Route::get('/', function()
	{
		$users = API::get('users');

		return View::make('index')->with('users', $users);
	});

> You don't need to include the API prefix or domain when internally calling an API endpoint.

#### Internal Request With Parameters

	API::with(['name' => 'Jason', 'location' => 'Australia'])->post('users');

	API::post('users', ['name' => 'Jason', 'location' => 'Australia']);

#### Targetting A Specific Version

	API::version('v2')->get('users');

### Authentication and Authorization
