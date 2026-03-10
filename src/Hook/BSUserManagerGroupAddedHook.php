<?php

namespace BlueSpice\UserManager\Hook;

use MediaWiki\Permissions\Authority;

interface BSUserManagerGroupAddedHook {
	/**
	 * @param string $name
	 * @param Authority $actor
	 * @return void
	 */
	public function onBSUserManagerGroupAdded( string $name, Authority $actor );
}
