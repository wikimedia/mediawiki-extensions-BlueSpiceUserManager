<?php

namespace BlueSpice\UserManager\Rest;

use MediaWiki\Config\GlobalVarConfig;
use MediaWiki\Context\RequestContext;
use MediaWiki\Exception\PermissionsError;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\CommonWebAPIs\Rest\UserQueryStore;
use MWStake\MediaWiki\Component\DataStore\IStore;
use MWStake\MediaWiki\Component\Utils\UtilityFactory;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Only exists to differentiate between the two stores
 */
class UserStore extends UserQueryStore {

	/**
	 * @param HookContainer $hookContainer
	 * @param ILoadBalancer $lb
	 * @param UserFactory $userFactory
	 * @param LinkRenderer $linkRenderer
	 * @param TitleFactory $titleFactory
	 * @param GlobalVarConfig $mwsgConfig
	 * @param UtilityFactory|null $utilityFactory
	 * @param PermissionManager $permissionManager
	 */
	public function __construct(
		HookContainer $hookContainer, ILoadBalancer $lb, UserFactory $userFactory, LinkRenderer $linkRenderer,
		TitleFactory $titleFactory, GlobalVarConfig $mwsgConfig, ?UtilityFactory $utilityFactory,
		private readonly PermissionManager $permissionManager
	) {
		parent::__construct(
			$hookContainer, $lb, $userFactory, $linkRenderer, $titleFactory, $mwsgConfig, $utilityFactory
		);
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
