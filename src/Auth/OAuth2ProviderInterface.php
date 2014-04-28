<?php namespace Dingo\Api\Auth;

interface OAuth2ProviderInterface {

	/**
	 * Determine if the authenticated access token has a given scope.
	 * 
	 * @param  string  $scope
	 * @return bool
	 */
	public function hasScope($scope);

}
