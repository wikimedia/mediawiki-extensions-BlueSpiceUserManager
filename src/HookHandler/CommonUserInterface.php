<?php

namespace BlueSpice\UserManager\HookHandler;

use BlueSpice\UserManager\GlobalActionsManager;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'GlobalActionsManager',
			[
				'ga-bluespice-usermanager' => [
					'factory' => static function () {
						return new GlobalActionsManager();
					}
				]
			]
		);
	}
}
