<?php

/**
 * UserManager Extension for BlueSpice
 *
 * Administration interface for adding, editing and deleting users.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit https://www.bluespice.com
 *
 * @author     Sebastian Ulbricht
 * @author     Stephan Muggli
 * @package    BlueSpice_Extensions
 * @subpackage UserManager
 * @copyright  Copyright (C) 2018 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */

namespace BlueSpice\UserManager;

use MediaWiki\Auth\TemporaryPasswordAuthenticationRequest;
use MediaWiki\Auth\UserDataAuthenticationRequest;
use MediaWiki\Auth\UsernameAuthenticationRequest;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\MediaWikiServices;
use MWTimestamp;
use Status;
use User;
use Wikimedia\Rdbms\Database;

class Extension extends \BlueSpice\Extension {
	/* These groups are not touched by the addtogroup tool */

	protected static $excludegroups = [ '*', 'user', 'autoconfirmed', 'emailconfirmed' ];

	/**
	 * Adds an user
	 * @param string $userName
	 * @param array $metaData
	 * @param \User|null $performer
	 * @return \Status
	 */
	public static function addUser( $userName, $metaData = [], \User $performer = null ) {
		// This is to overcome username case issues with custom AuthPlugin (i.e. LDAPAuth)
		// LDAPAuth would otherwise turn the username to first-char-upper-rest-lower-case
		// At the end of this method we switch $_SESSION['wsDomain'] back again
		$tmpDomain = isset( $_SESSION['wsDomain'] ) ? $_SESSION['wsDomain'] : '';
		$_SESSION['wsDomain'] = 'local';

		if ( !$performer ) {
			$performer = \RequestContext::getMain()->getUser();
		}

		$authManager = MediaWikiServices::getInstance()->getAuthManager();

		$usernameReq = new UsernameAuthenticationRequest();
		$usernameReq->username = $userName;

		$userDataReq = new UserDataAuthenticationRequest();
		$userDataReq->email = $metaData['email'] ?? '';
		$userDataReq->realname = $metaData['realname'] ?? '';
		$userDataReq->username = $userName;

		$tempPassReq = new TemporaryPasswordAuthenticationRequest();
		$tempPassReq->username = $userName;
		$tempPassReq->password = $metaData['password'] ?? '';
		$tempPassReq->mailpassword = !empty( $metaData['email'] );

		$authResponse = $authManager->beginAccountCreation( $performer, [
			$usernameReq,
			$userDataReq,
			$tempPassReq
		], '' );

		if ( $authResponse->status !== $authResponse::PASS ) {
			return Status::newFatal( $authResponse->message );
		}

		$user = User::newFromName( $userName, true );
		if ( $user->getEmail() ) {
			// Auto-verify mail address, since user already used it for first login
			$user->setEmailAuthenticationTimestamp( MWTimestamp::now() );
			$user->saveSettings();
		}

		$status = static::setBlock( $metaData, $user, $performer );
		if ( !$status->isOK() ) {
			return $status;
		}

		$status = Status::newGood( $user );
		$_SESSION['wsDomain'] = $tmpDomain;

		$services = MediaWikiServices::getInstance();
		$userManager = $services->getService( 'BSExtensionFactory' )
			->getExtension( 'BlueSpiceUserManager' );
		$services->getHookContainer()->run( 'BSUserManagerAfterAddUser', [
			$userManager,
			$user,
			$metaData,
			&$status,
			$performer
		] );

		$siteStatsUpdate = new \SiteStatsUpdate( 0, 0, 0, 0, 1 );
		$siteStatsUpdate->doUpdate();

		return $status;
	}

	/**
	 * Changes user password
	 * @param \User $user
	 * @param array $passwordData
	 * @param \User|null $performer
	 * @return \Status|\StatusValue
	 */
	public static function editPassword( \User $user, $passwordData = [], \User $performer = null ) {
		$status = Status::newGood();

		if ( !$performer ) {
			$performer = \RequestContext::getMain()->getUser();
		}

		$strategy = $passwordData['strategy'];

		if ( $strategy === 'reset' ) {
			if ( !$user->canReceiveEmail() ) {
				return Status::newFatal( 'bs-usermanager-no-mail' );
			}
			$passwordReset = MediaWikiServices::getInstance()->getPasswordReset();
			return $passwordReset->execute( $performer, $user->getName(), $user->getEmail() );
		}

		$password = $passwordData['password'];
		if ( empty( $passwordData['password'] ) ) {
			$newStatus = Status::newFatal( 'bs-usermanager-invalid-pwd' );
			$status->merge( $newStatus );
			return $status;
		}

		if ( !empty( $passwordData['password'] ) ) {
			if ( !$user->isValidPassword( $password ) ) {
				$newStatus = Status::newFatal( 'bs-usermanager-invalid-pwd' );
				$status->merge( $newStatus );
			}
			if ( strtolower( $user->getName() ) == strtolower( $password ) ) {
				$newStatus = Status::newFatal( 'password-name-match' );
				$status->merge( $newStatus );
			}
			$rePassword = $passwordData['repassword'];
			if ( !isset( $rePassword ) || $password !== $rePassword ) {
				$newStatus = Status::newFatal( 'badretype' );
				$status->merge( $newStatus );
			}
		}

		$status->merge( $user->changeAuthenticationData( [
			'password' => $password,
			'retype' => $password ] )
		);

		if ( $status->isOk() ) {
			$user->saveSettings();
		}

		return $status;
	}

	/**
	 * Edits or adds an user
	 * @param \User $user
	 * @param array $metaData
	 * @param bool $createIfNotExists
	 * @param \User|null $performer
	 * @return \Status
	 */
	public static function editUser(
		\User $user, $metaData = [], $createIfNotExists = false, \User $performer = null
	) {
		$status = Status::newGood();

		if ( !$performer ) {
			$performer = \RequestContext::getMain()->getUser();
		}

		if ( !empty( $metaData['realname'] ) ) {
			if ( strpos( $metaData['realname'], '\\' ) ) {
				$newStatus = Status::newFatal(
						'bs-usermanager-invalid-realname'
				);
				$status->merge( $newStatus );
			}
		}
		if ( !empty( $metaData['email'] ) ) {
			if ( \Sanitizer::validateEmail( $metaData['email'] ) === false ) {
				$newStatus = Status::newFatal(
						'bs-usermanager-invalid-email-gen'
				);
				$status->merge( $newStatus );
			}
		}
		if ( !$status->isOK() ) {
			return $status;
		}

		if ( !empty( $metaData['email'] ) ) {
			$user->setEmail( $metaData['email'] );
			$user->setEmailAuthenticationTimestamp( MWTimestamp::now() );
		} else {
			$user->setEmail( '' );
		}
		if ( !empty( $metaData['realname'] ) ) {
			$user->setRealName( $metaData['realname'] );
		} else {
			$user->setRealName( '' );
		}

		$user->saveSettings();

		$status = static::setBlock( $metaData, $user, $performer );
		if ( !$status->isOK() ) {
			return $status;
		}

		$services = MediaWikiServices::getInstance();
		$userManager = $services->getService( 'BSExtensionFactory' )
			->getExtension( 'BlueSpiceUserManager' );

		$services->getHookContainer()->run( 'BSUserManagerAfterEditUser', [
			$userManager,
			$user,
			$metaData,
			&$status,
			$performer,
		] );

		return Status::newGood( $user );
	}

	/**
	 * @param array $data
	 * @param User $user
	 * @param User $performer
	 * @return Status|null
	 */
	protected static function setBlock( array $data, User $user, User $performer ) {
		if ( !isset( $data['enabled'] ) ) {
			return Status::newGood();
		}
		if ( $data['enabled'] === false && $user->getBlock() === null ) {
			return self::disableUser( $user, $performer, $status );
		} elseif ( $data['enabled'] === true && $user->getBlock() !== null ) {
			return self::enableUser( $user, $performer, $status );
		}

		return Status::newGood();
	}

	/**
	 * Disables a user in the system.
	 * @param \User $user The user to be disabled.
	 * @param \User $performer The user that requests the disabling
	 * @param \Status|null &$status The status of the operation so far
	 * @return \Status
	 */
	public static function disableUser( \User $user, \User $performer, \Status &$status = null ) {
		if ( $status === null ) {
			$status = Status::newGood();
		}
		if ( $user->getId() == $performer->getId() ) {
			$status->setResult( false );
			$status->fatal( 'bs-usermanager-no-self-block' );
			return $status;
		}

		# Create block object.
		$block = new DatabaseBlock();
		$block->setBlocker( $performer );
		$block->setTarget( $user );
		$block->setExpiry( 'infinity' );
		$block->setReason( \wfMessage( 'bs-usermanager-log-user-disabled', $user->getName() )->text() );
		$block->isEmailBlocked( true );
		$block->isCreateAccountBlocked( false );
		$block->isUsertalkEditAllowed( true );
		$block->isHardblock( true );
		$block->isAutoblocking( false );

		$reason = [ 'hookaborted' ];
		$res = MediaWikiServices::getInstance()->getHookContainer()->run( 'BlockIp', [
			&$block,
			&$performer,
			&$reason
		] );
		if ( !$res ) {
			$status->setResult( false );
			$status->fatal( $reason );
			return $status;
		}

		# Try to insert block. Is there a conflicting block?
		$blockStatus = $block->insert();
		if ( !$blockStatus ) {
			$status->setResult( false );
			$status->fatal( 'bs-usermanager-block-error', $user->getName() );
		}
		return $status;
	}

	/**
	 * Enables a disabled user
	 * @param \User $user The user to be enabled
	 * @param \User $performer The user that requests the enabling
	 * @param \Status|null &$status The status of the operation so far
	 * @return \Status
	 */
	public static function enableUser( \User $user, \User $performer, \Status &$status = null ) {
		if ( $status === null ) {
			$status = Status::newGood();
		}

		$block = DatabaseBlock::newFromTarget( $user );
		if ( !$block ) {
			// fallback whenever the Block could not be created because the user
			// is invalid in some form due to unknown reasons. ERM:25175
			$status->setResult( false );
			$status->fatal( 'bs-usermanager-unblock-error', $user->getName() );
			return $status;
		}
		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		$reason = [];
		if ( !$hookContainer->run( 'UnblockUser', [ $block, $performer, &$reason ] ) ) {
			$status->setResult( false );
			$status->fatal( $reason[0] ?? 'bs-usermanager-unblock-error', $user->getName() );

			return $status;

		}

		$block->setBlocker( $performer );
		$blockStatus = $block->delete();

		if ( !$blockStatus ) {
			$status->setResult( false );
			$status->fatal( 'bs-usermanager-unblock-error', $user->getName() );
		}

		return $status;
	}

	/**
	 * Deletes an user form the database
	 * TODO: Merge into DeleteUser
	 * @param \User $user
	 * @param \User|null $performer
	 * @return \Status
	 */
	public static function deleteUser( \User $user, \User $performer = null ) {
		if ( $user->getId() == 0 ) {
			return Status::newFatal( 'bs-usermanager-idnotexist' );
		}

		if ( $user->getId() == 1 ) {
			return Status::newFatal( 'bs-usermanager-admin-nodelete' );
		}

		if ( !$performer ) {
			$performer = \RequestContext::getMain()->getUser();
		}
		if ( $user->getId() == $performer->getId() ) {
			return Status::newFatal( 'bs-usermanager-self-nodelete' );
		}

		$status = Status::newGood( $user );
		$user->load( \User::READ_LATEST );
		if ( $user->getUserPage()->exists() ) {
			$userPageArticle = new \Article( $user->getUserPage() );
			$userPageArticle->doDelete( \wfMessage( 'bs-usermanager-db-error' )->plain() );
		}

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_MASTER );
		$fname = __METHOD__;
		$section = $dbw->startAtomic( $fname, Database::ATOMIC_CANCELABLE );
		try {
			$dbw->delete( 'user', [ 'user_id' => $user->getId() ], $fname );
			$dbw->delete( 'user_groups', [ 'ug_user' => $user->getId() ], $fname );
			$dbw->delete( 'user_newtalk', [ 'user_id' => $user->getId() ], $fname );
			$dbw->delete( 'user_properties', [ 'up_user' => $user->getId() ], $fname );
			$countUsers = $dbw->selectField( 'user', 'COUNT(*)', [] );
			$dbw->update(
				'site_stats', [ 'ss_users' => $countUsers ],
				[ 'ss_row_id' => 1 ], $fname
			);
			$dbw->endAtomic( $fname );
		} catch ( \Exception $ex ) {
			$dbw->cancelAtomic( $fname, $section );

			$status->merge( Status::newFatal( 'bs-usermanager-db-error' ) );
			return $status;
		}

		$userManager = MediaWikiServices::getInstance()->getService( 'BSExtensionFactory' )
			->getExtension( 'BlueSpiceUserManager' );
		MediaWikiServices::getInstance()->getHookContainer()->run(
			'BSUserManagerAfterDeleteUser',
			[
				$userManager,
				$user,
				&$status,
				$performer,
			]
		);

		return $status;
	}

	/**
	 * Removes / adds groups to a user
	 * See also https://www.mediawiki.org/wiki/Manual:$wgAddGroups
	 * @param \User $user
	 * @param array $groups
	 * @return \Status
	 */
	public static function setGroups( \User $user, $groups = [] ) {
		$loggedInUser = \RequestContext::getMain()->getUser();
		$attemptChangeSelf = $loggedInUser->getId() == $user->getId();

		$checkSelfSysopRemove = $attemptChangeSelf
			&& in_array( 'sysop', $loggedInUser->getEffectiveGroups() )
			&& !in_array( 'sysop', $groups );
		if ( $checkSelfSysopRemove ) {
			return Status::newFatal( 'bs-usermanager-no-self-desysop' );
		}

		$oldUGMs = $user->getGroupMemberships();
		$currentGroups = $user->getGroups();
		$addGroups = array_diff( $groups, $currentGroups );
		$removeGroups = array_diff( $currentGroups, $groups );
		$reallyAdd = [];
		$reallyRemove = [];

		$changeableGroups = $loggedInUser->changeableGroups();

		foreach ( $addGroups as $group ) {
			if ( in_array( $group, self::$excludegroups ) ) {
				continue;
			}
			if (
				!in_array( $group, $changeableGroups['add'] ) &&
				( !$attemptChangeSelf || !in_array( $group, $changeableGroups['add-self'] ) )
			) {
				return Status::newFatal( 'bs-usermanager-group-add-not-allowed', $group );
			}
			$reallyAdd[] = $group;
			$user->addGroup( $group );
		}
		foreach ( $removeGroups as $group ) {
			if ( in_array( $group, self::$excludegroups ) ) {
				continue;
			}

			if (
				!in_array( $group, $changeableGroups['remove'] ) &&
				( !$attemptChangeSelf || !in_array( $group, $changeableGroups['remove-self'] ) )
			) {
				return Status::newFatal( 'bs-usermanager-group-remove-not-allowed', $group );
			}
			$reallyRemove[] = $group;
			$user->removeGroup( $group );
		}

		$status = Status::newGood( $user );
		MediaWikiServices::getInstance()->getHookContainer()->run( 'UserGroupsChanged', [
			$user,
			$reallyAdd,
			$reallyRemove,
			$loggedInUser,
			'',
			$oldUGMs,
			$user->getGroupMemberships()
		] );
		MediaWikiServices::getInstance()->getHookContainer()->run(
			'BSUserManagerAfterSetGroups',
			[
				$user,
				$groups,
				$addGroups,
				$removeGroups,
				self::$excludegroups,
				&$status
			]
		);

		$user->invalidateCache();
		return $status;
	}
}
