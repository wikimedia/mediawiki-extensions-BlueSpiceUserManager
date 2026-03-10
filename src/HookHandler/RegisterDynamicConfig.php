<?php

namespace BlueSpice\UserManager\HookHandler;

use BlueSpice\UserManager\DynamicConfig\Groups;
use MWStake\MediaWiki\Component\DynamicConfig\Hook\MWStakeDynamicConfigRegisterConfigsHook;

class RegisterDynamicConfig implements MWStakeDynamicConfigRegisterConfigsHook {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeDynamicConfigRegisterConfigs( array &$configs ): void {
		$configs[] = new Groups();
	}
}
