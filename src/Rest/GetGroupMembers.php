<?php

namespace BlueSpice\UserManager\Rest;

use BlueSpice\UserManager\GroupManager;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class GetGroupMembers extends SimpleHandler {

	/**
	 * @param GroupManager $groupManager
	 */
	public function __construct( private readonly GroupManager $groupManager ) {
	}

	public function execute() {
		$params = $this->getValidatedParams();

		$members = $this->groupManager->getGroupMembers(
			$params['name']
		);
		$member = [];
		foreach ( $members as $user ) {
			$member[][ 'username' ] = $user;
		}
		$results['member'] = $member;
		return $this->getResponseFactory()->createJson( [ 'results' => $member ] );
	}

	public function getParamSettings(): array {
		return [
			'name' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}

}
