<?php

namespace BlueSpice\UserManager\Rest;

use BlueSpice\UserManager\GroupManager;
use MediaWiki\Context\RequestContext;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use Throwable;
use Wikimedia\ParamValidator\ParamValidator;

class AddGroup extends SimpleHandler {

	/** @var GroupManager */
	private GroupManager $groupManager;

	/**
	 * @param GroupManager $groupManager
	 */
	public function __construct( GroupManager $groupManager ) {
		$this->groupManager = $groupManager;
	}

	/**
	 * @return true
	 */
	public function needsWriteAccess() {
		return true;
	}

	public function execute() {
		$params = $this->getValidatedParams();
		try {
			$groupname = $this->groupManager->addGroup( $params['name'], RequestContext::getMain()->getAuthority() );
		} catch ( Throwable $e ) {
			throw new HttpException( $e->getMessage(), 500 );
		}
		return $this->getResponseFactory()->createJson( [ 'groupname' => $groupname ] );
	}

	public function getParamSettings(): array {
		return [
			'name' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
		];
	}
}
