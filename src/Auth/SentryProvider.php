<?php
/**
 * Created by PhpStorm.
 * User: kingpin
 * Date: 5/20/14
 * Time: 6:40 AM
 */

namespace Dingo\Api\Auth;

use Cartalyst\Sentry\Sentry;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class SentryProvider extends \Dingo\Api\Auth\Provider
{
    public function __construct(Sentry $sentry, $identifier = 'email')
    {
        $this->sentry = $sentry;
        $this->identifier = $identifier;
    }

    public function authenticate(Request $request, Route $route)
    {
        if (($user = $this->sentry->getUser())) {
            return $user->getId();
        }
        // Logic to authenticate the user for the given request. If it fails...
        throw new UnauthorizedHttpException(null, 'Your username or password is incorrect.');
    }

    /**
     * Get the providers authorization method.
     *
     * @return string
     */
    public function getAuthorizationMethod()
    {
        return 'sentry';
    }
}