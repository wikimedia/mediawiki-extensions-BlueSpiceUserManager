<?php

namespace BlueSpice\UserManager\Rest;

use MediaWiki\Context\RequestContext;
use Throwable;

class BlockUsersHandler extends UserMassActionHandler {

	/**
	 * @return true
	 */
	public function needsWriteAccess() {
		return true;
	}

	public function execute() {
		$users = $this->getUsers();
		$res = [];
		foreach ( $users as $user ) {
			try {
				$this->userManager->blockUser( $user, RequestContext::getMain()->getUser() );
				$res[$user->getName()] = true;
			} catch ( Throwable $ex ) {
				$res[$user->getName()] = $ex->getMessage();
			}
		}
		return $this->getResponseFactory()->createJson( $res );
	}
}
