<?php

namespace BlueSpice\UserManager\Hook;

use MediaWiki\SpecialPage\Hook\SpecialPage_initListHook;

class RemoveSpecialBlock implements SpecialPage_initListHook {

	/**
	 * @inheritDoc
	 */
	public function onSpecialPage_initList( &$list ) {
		// All user blocking should be done over BSUserManager
		unset( $list['Block'] );
	}
}
