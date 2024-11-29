<?php

namespace BlueSpice\UserManager\Rest;

use MediaWiki\Context\RequestContext;
use Throwable;
use Wikimedia\ParamValidator\ParamValidator;

class SetGroupsHandler extends UserMassActionHandler {

	/**
	 * @return true
	 */
	public function needsWriteAccess() {
		return true;
	}

	public function execute() {
		$users = $this->getUsers();
		$groups = $this->getValidatedBody()['groups'];
		$res = [];
		foreach ( $users as $user ) {
			try {
				$this->userManager->setGroups( $user, $groups, RequestContext::getMain()->getUser() );
				$res[$user->getName()] = true;
			} catch ( Throwable $ex ) {
				$res[$user->getName()] = $ex->getMessage();
			}
		}

		return $this->getResponseFactory()->createJson( $res );
	}

	/**
	 * @return array[]
	 */
	public function getBodyParamSettings(): array {
		return array_merge( parent::getBodyParamSettings(), [
			'groups' => [
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_REQUIRED => false
			],
		] );
	}
}
