<?php

namespace BlueSpice\UserManager\Rest;

use BlueSpice\UserManager\UserManager;
use MediaWiki\Context\RequestContext;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\User\UserFactory;
use Throwable;
use Wikimedia\ParamValidator\ParamValidator;

class ChangePasswordHandler extends SimpleHandler {

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

	public function execute() {
		$username = $this->getValidatedParams()['username'];
		$user = $this->userFactory->newFromName( $username );
		if ( !$user ) {
			throw new HttpException( 'Invalid username', 500 );
		}
		$params = $this->getValidatedBody();
		$exists = $user->isRegistered();
		if ( !$exists ) {
			throw new HttpException( 'User does not exist', 404 );
		}

		try {
			$this->userManager->resetPassword( $user, $params, RequestContext::getMain()->getUser() );
			return $this->getResponseFactory()->createNoContent();
		} catch ( Throwable $ex ) {
			throw new HttpException( $ex->getMessage(), 500 );
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
			'strategy' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			'password' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			'repassword' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
		];
	}
}
