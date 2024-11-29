<?php

namespace BlueSpice\UserManager\Rest;

use MediaWiki\Context\RequestContext;
use MediaWiki\Rest\HttpException;
use Throwable;
use Wikimedia\ParamValidator\ParamValidator;

class AddUserHandler extends UpdateUserHandler {

	public function execute() {
		$user = $this->getAssertedUser();
		$params = $this->getValidatedBody();
		$this->assertPasswordsMatch( $params['password'], $params['repassword'] );

		try {
			$this->userManager->addUser( $user, $params, RequestContext::getMain()->getUser() );
			// Recreate user after creation
			$user = $this->userFactory->newFromName( $user->getName() );
			$this->trySetGroups( $user );
			return $this->getResponseFactory()->createNoContent();
		} catch ( Throwable $ex ) {
			throw new HttpException( $ex->getMessage(), 500 );
		}
	}

	/**
	 * @return array[]
	 */
	public function getBodyParamSettings(): array {
		return array_merge( parent::getBodyParamSettings(), [
			'password' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'repassword' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		] );
	}

	/**
	 * @param string $password
	 * @param string $repassword
	 * @return void
	 * @throws HttpException
	 */
	private function assertPasswordsMatch( string $password, string $repassword ) {
		if ( $password !== $repassword ) {
			throw new HttpException( 'Passwords do not match', 400 );
		}
	}
}
