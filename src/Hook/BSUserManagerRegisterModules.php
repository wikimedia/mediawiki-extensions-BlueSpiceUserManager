<?php

namespace BlueSpice\UserManager\Hook;

use MediaWiki\User\User;

interface BSUserManagerRegisterModules {

	/**
	 * @param string[] &$modules
	 * @param User $user
	 * @return void
	 */
	public function onBSUserManagerRegisterModules(
		&$modules,
		User $user
	): void;

}
