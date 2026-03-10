<?php

namespace BlueSpice\UserManager\Hook;

use MediaWiki\Permissions\Authority;

interface BSUserManagerGroupDeletedHook {
	/**
	 * @param string $name
	 * @param Authority $actor
	 * @return void
	 */
	public function onBSUserManagerGroupDeleted( string $name, Authority $actor );
}
