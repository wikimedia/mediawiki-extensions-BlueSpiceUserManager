<?php

namespace BlueSpice\UserManager\Rest;

use BlueSpice\UserManager\UserManager;
use MediaWiki\Permissions\Authority;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;

class AddUserToGroup extends UserGroupActions {

	/**
	 * @param UserManager $userManager
	 * @param UserFactory $userFactory
	 */
	public function __construct( UserManager $userManager, UserFactory $userFactory ) {
		parent::__construct( $userManager, $userFactory );
	}

	/**
	 * @inheritDoc
	 */
	protected function doExecute( User $user, string $group, Authority $actor ) {
		$this->userManager->addUserToGroup( $user, $group, $actor );
	}
}
