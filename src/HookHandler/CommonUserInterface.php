<?php

namespace BlueSpice\UserManager\HookHandler;

use BlueSpice\UserManager\EnhancedGlobalActionsAdministration;
use BlueSpice\UserManager\GlobalActionsAdministration;
use MediaWiki\Context\RequestContext;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly TitleFactory $titleFactory
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$skin = RequestContext::getMain()->getSkin();
		$registry->register(
			'GlobalActionsAdministration',
			[
				'ga-bluespice-usermanager' => [
					'factory' => function () use ( $skin ) {
						if ( is_a( $skin, 'SkinBlueSpiceEclipseSkin', true ) ) {
							return new EnhancedGlobalActionsAdministration( $this->titleFactory );
						}
						return new GlobalActionsAdministration();
					}
				]
			]
		);
	}
}
