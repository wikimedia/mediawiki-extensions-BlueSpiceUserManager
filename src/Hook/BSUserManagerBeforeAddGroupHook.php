<?php

namespace BlueSpice\UserManager\Hook;

use MediaWiki\Permissions\Authority;

interface BSUserManagerBeforeAddGroupHook {

	/**
	 * @param string &$name
	 * @param Authority $actor
	 * @return void
	 */
	public function onBSUserManagerBeforeAddGroup( string &$name, Authority $actor );
}
