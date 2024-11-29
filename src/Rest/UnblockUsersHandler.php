<?php

namespace BlueSpice\UserManager\Rest;

use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Context\RequestContext;
use Throwable;

class UnblockUsersHandler extends UserMassActionHandler {

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
				$block = $this->userManager->getBlock( $user );
				if ( $block instanceof DatabaseBlock ) {
					$this->userManager->unblockUser( $block, $user, RequestContext::getMain()->getUser() );
				}
				$res[$user->getName()] = true;
			} catch ( Throwable $ex ) {
				$res[$user->getName()] = $ex->getMessage();
			}
		}
		return $this->getResponseFactory()->createJson( $res );
	}
}
