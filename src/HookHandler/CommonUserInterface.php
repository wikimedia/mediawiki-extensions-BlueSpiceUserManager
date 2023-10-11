<?php

namespace BlueSpice\UserManager\HookHandler;

use BlueSpice\UserManager\GlobalActionsAdministration;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'GlobalActionsAdministration',
			[
				'ga-bluespice-usermanager' => [
					'factory' => static function () {
						return new GlobalActionsAdministration();
					}
				]
			]
		);
	}
}
