<?php

namespace BlueSpice\UserManager\Rest;

use BlueSpice\UserManager\UserManager;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\User\UserFactory;
use Wikimedia\ParamValidator\ParamValidator;

abstract class UserMassActionHandler extends SimpleHandler {

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
	 * @return array
	 */
	protected function getUsers(): array {
		$users = $this->getValidatedBody()['users'];
		$users = array_map( function ( $uname ) {
			return $this->userFactory->newFromName( $uname );
		}, $users );
		return array_filter( $users );
	}

	/**
	 * @return array[]
	 */
	public function getBodyParamSettings(): array {
		return [
			'users' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}
