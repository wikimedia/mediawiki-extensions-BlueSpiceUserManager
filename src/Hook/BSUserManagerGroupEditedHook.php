<?php

namespace BlueSpice\UserManager\Hook;

use MediaWiki\Permissions\Authority;

interface BSUserManagerGroupEditedHook {
	/**
	 * @param string $oldName
	 * @param string $newName
	 * @param Authority $actor
	 * @return void
	 */
	public function onBSUserManagerGroupEdited( string $oldName, string $newName, Authority $actor );
}
