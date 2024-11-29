<?php

use BlueSpice\UserManager\UserManager;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'BlueSpice.UserManager.Manager' => static function ( MediaWikiServices $services ) {
		$manager = new UserManager(
			$services->get( 'BlockManager' ),
			$services->get( 'DatabaseBlockStore' ),
			$services->get( 'HookContainer' ),
			$services->get( 'AuthManager' ),
			$services->get( 'UserGroupManager' ),
			$services->get( 'PasswordReset' )
		);
		$manager->setLogger( LoggerFactory::getInstance( 'BlueSpiceUserManager' ) );
		return $manager;
	},
];
