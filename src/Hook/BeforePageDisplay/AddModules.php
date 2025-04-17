<?php

namespace BlueSpice\UserManager\Hook\BeforePageDisplay;

use BlueSpice\Hook\BeforePageDisplay;
use MediaWiki\MediaWikiServices;

class AddModules extends BeforePageDisplay {

	protected function skipProcessing() {
		$title = $this->getContext()->getTitle();
		if ( !$title || !$title->isSpecial( 'UserManager' ) ) {
			return true;
		}
		return false;
	}

	protected function doProcess() {
		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		$modules = [];
		$user = $this->out->getUser();
		$hookContainer->run( 'BSUserManagerRegisterModules', [ &$modules, $user ] );

		if ( !empty( $modules ) ) {
			foreach ( $modules as $module ) {
				$this->out->addModules( $module );
			}

		}
		return true;
	}
}
