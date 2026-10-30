<?php

namespace BlueSpice\UserManager\Rest;

use MediaWiki\Config\GlobalVarConfig;
use MediaWiki\Context\RequestContext;
use MediaWiki\Exception\PermissionsError;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Permissions\PermissionManager;
use MWStake\MediaWiki\Component\DataStore\IStore;
use MWStake\MediaWiki\Component\Utils\UtilityFactory;

/**
 * Only exists to differentiate between the two stores
 */
class GroupStore extends \MWStake\MediaWiki\Component\CommonWebAPIs\Rest\GroupStore {

	public function __construct(
		HookContainer $hookContainer, UtilityFactory $utilityFactory, GlobalVarConfig $mwsgConfig,
		private readonly PermissionManager $permissionManager
	) {
		parent::__construct( $hookContainer, $utilityFactory, $mwsgConfig );
	}

	/**
	 * @return IStore
	 * @throws PermissionsError
	 */
	protected function getStore(): IStore {
		if ( !$this->permissionManager->userHasRight( RequestContext::getMain()->getUser(), 'wikiadmin' ) ) {
			throw new PermissionsError( 'wikiadmin' );
		}
		return parent::getStore();
	}
}
