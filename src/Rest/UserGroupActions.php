<?php

namespace BlueSpice\UserManager\Rest;

use BlueSpice\UserManager\UserManager;
use MediaWiki\Context\RequestContext;
use MediaWiki\Permissions\Authority;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use Wikimedia\ParamValidator\ParamValidator;

abstract class UserGroupActions extends SimpleHandler {

	protected UserManager $userManager;

	protected UserFactory $userFactory;

	/**
	 * @param UserManager $userManager
	 * @param UserFactory $userFactory
	 */
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
		$params = $this->getValidatedParams();
		$bodyParams = $this->getValidatedBody();

		$group = $params['group'];
		$user = $this->userFactory->newFromName( $bodyParams['user'] );
		if ( !$user || !$user->isRegistered() ) {
			throw new HttpException( 'User not found', 404 );
		}
		$actor = RequestContext::getMain()->getUser();
		$this->doExecute( $user, $group, $actor );
		return $this->getResponseFactory()->createNoContent();
	}

	/**
	 * @param User $user
	 * @param string $group
	 * @param Authority $actor
	 * @return void
	 */
	protected function doExecute( User $user, string $group, Authority $actor ) {
	}

	public function getParamSettings(): array {
		return [
			'group' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
		];
	}

	/**
	 * @return array[]
	 */
	public function getBodyParamSettings(): array {
		return [
			'user' => [
				self::PARAM_SOURCE => 'body',
				'type' => 'string',
				'required' => true
			]
		];
	}
}
