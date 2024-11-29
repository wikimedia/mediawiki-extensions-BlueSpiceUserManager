<?php

namespace BlueSpice\UserManager\Rest;

use BlueSpice\UserManager\UserManager;
use MediaWiki\Context\RequestContext;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use PermissionsError;
use Throwable;
use Wikimedia\ParamValidator\ParamValidator;

class UpdateUserHandler extends SimpleHandler {

	/**
	 * @var UserManager
	 */
	protected $userManager;

	/**
	 * @var UserFactory
	 */
	protected $userFactory;

	public function __construct( UserManager $userManager, UserFactory $userFactory ) {
		$this->userManager = $userManager;
		$this->userFactory = $userFactory;
	}

	/**
	 * @return true
	 */
	public function needsWriteAccess() {
		return true;
	}

	/**
	 * @return Response
	 * @throws HttpException
	 */
	public function execute() {
		$user = $this->getAssertedUser();
		$params = $this->getValidatedBody();
		$exists = $user->isRegistered();
		if ( !$exists ) {
			throw new HttpException( 'User does not exist', 404 );
		}

		try {
			$this->userManager->updateUser( $user, $params, RequestContext::getMain()->getUser() );
			$this->trySetGroups( $user );
			return $this->getResponseFactory()->createNoContent();
		} catch ( Throwable $ex ) {
			throw new HttpException( $ex->getMessage(), 500 );
		}
	}

	/**
	 * @return User
	 * @throws HttpException
	 */
	protected function getAssertedUser(): User {
		$username = $this->getValidatedParams()['username'];
		$user = $this->userFactory->newFromName( $username );
		if ( !$user ) {
			throw new HttpException( 'Invalid username', 500 );
		}
		return $user;
	}

	/**
	 * @param User $user
	 * @return void
	 * @throws PermissionsError
	 */
	protected function trySetGroups( User $user ) {
		$params = $this->getValidatedBody();
		if ( isset( $params['groups'] ) ) {
			$this->userManager->setGroups( $user, $params['groups'], RequestContext::getMain()->getUser() );
		}
	}

	/**
	 * @return array[]
	 */
	public function getParamSettings() {
		return [
			'username' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}

	/**
	 * @return array[]
	 */
	public function getBodyParamSettings(): array {
		return [
			'realName' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			'email' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			'enabled' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_REQUIRED => true
			],
			'groups' => [
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_REQUIRED => false
			],
		];
	}
}
