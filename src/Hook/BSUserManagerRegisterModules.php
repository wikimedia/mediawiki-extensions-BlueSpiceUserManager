<?php

namespace BlueSpice\UserManager\Hook;

use User;

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
