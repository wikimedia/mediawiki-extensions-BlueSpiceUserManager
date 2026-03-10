<?php

use BlueSpice\UserManager\GroupManager;
use BlueSpice\UserManager\Logging\GroupManagerSpecialLogLogger;
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
	'BlueSpice.UserManager.GroupManager' => static function ( MediaWikiServices $services ) {
		return new GroupManager(
			$services->getService( 'MWStakeDynamicConfigManager' ),
			$services->getDBLoadBalancer(),
			$services->getMainConfig(),
			$services->getHookContainer(),
			LoggerFactory::getInstance( 'BlueSpiceUserManager.GroupManager' ),
			new GroupManagerSpecialLogLogger()
		);
	},
];
