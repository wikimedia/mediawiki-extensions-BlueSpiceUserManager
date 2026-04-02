<?php

namespace BlueSpice\UserManager\HookHandler;

use BlueSpice\UserManager\EnhancedGlobalActionsAdministration;
use BlueSpice\UserManager\GlobalActionsAdministration;
use MediaWiki\Context\RequestContext;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$skin = RequestContext::getMain()->getSkin();
		$registry->register(
			'GlobalActionsAdministration',
			[
				'ga-bluespice-usermanager' => [
					'factory' => static function () use ( $skin ) {
						if ( is_a( $skin, 'SkinBlueSpiceEclipseSkin', true ) ) {
							return new EnhancedGlobalActionsAdministration();
						}
						return new GlobalActionsAdministration();
					}
				]
			]
		);
	}
}
