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
 * For further information visit http://www.bluespice.com
 *
 * @author     Sebastian Ulbricht
 * @author     Stephan Muggli
 * @package    BlueSpice_Extensions
 * @subpackage UserManager
 * @copyright  Copyright (C) 2018 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 * @filesource
 */

namespace BlueSpice\UserManager;

class Extension extends \BlueSpice\Extension {

	/* These groups are not touched by the addtogroup tool */
	protected static $excludegroups = array( '*', 'user', 'autoconfirmed', 'emailconfirmed' );

	/**
	 * Adds an user
	 * @param string $sUserName
	 * @param array $aMetaData
	 * @return Status
	 */
	public static function addUser( $sUserName, $aMetaData = array(), \User $oPerformer = null ) {
		//This is to overcome username case issues with custom AuthPlugin (i.e. LDAPAuth)
		//LDAPAuth woud otherwise turn the username to first-char-upper-rest-lower-case
		//At the end of this method we switch $_SESSION['wsDomain'] back again
		$tmpDomain = isset( $_SESSION['wsDomain'] ) ? $_SESSION['wsDomain'] : '';
		$_SESSION['wsDomain'] = 'local';

		$oStatus = \Status::newGood();

		if( !$oPerformer ) {
			$oPerformer = \RequestContext::getMain()->getUser();
		}

		$sUserName = ucfirst( $sUserName );
		$oUser = \User::newFromName( $sUserName, true );
		if ( !$oUser ) {
			return \Status::newFatal( 'bs-usermanager-invalid-uname' );
		}
		if( $oUser->getId() !== 0 ) {
			return \Status::newFatal( 'bs-usermanager-user-exists' );
		}

		$sPass = $aMetaData['password'];
		if ( !empty( $aMetaData['password'] ) || $bNew ) {
			if( !$oUser->isValidPassword( $sPass ) ) {
				$oNewStatus = \Status::newFatal( 'bs-usermanager-invalid-pwd' );
				$oStatus->merge( $oNewStatus );
			}
			if ( strtolower( $oUser->getName() ) == strtolower( $sPass ) ) {
				$oNewStatus = \Status::newFatal( 'password-name-match' );
				$oStatus->merge( $oNewStatus );
			}
			$sRePass = $aMetaData['repassword'];
			if ( !isset($sRePass) || $sPass !== $sRePass ) {
				$oNewStatus = \Status::newFatal( 'badretype' );
				$oStatus->merge( $oNewStatus );
			}
		}

		if( !empty($aMetaData['realname']) ) {
			if ( strpos( $aMetaData['realname'], '\\' ) ) {
				$oNewStatus = \Status::newFatal(
					'bs-usermanager-invalid-realname'
				);
				$oStatus->merge( $oNewStatus );
			}
		}
		if( !empty($aMetaData['email']) ) {
			if ( \Sanitizer::validateEmail( $aMetaData['email'] ) === false ) {
				$oNewStatus = \Status::newFatal(
					'bs-usermanager-invalid-email-gen'
				);
				$oStatus->merge( $oNewStatus );
			}
		}
		if( !$oStatus->isOK() ) {
			return $oStatus;
		}

		$oUser->addToDatabase();
		$oUser->setToken();

		if( !empty($sPass) ) {
			$oUser->setPassword( $sPass );
		}
		if( !empty($aMetaData['email']) ) {
			$oUser->setEmail( $aMetaData['email'] );
		} else {
			$oUser->setEmail('');
		}
		if( !empty($aMetaData['realname']) ) {
			$oUser->setRealName( $aMetaData['realname'] );
		} else {
			$oUser->setRealName('');
		}

		$oUser->saveSettings();

		if( isset( $aMetaData['enabled'] ) ) {
			if ( $aMetaData['enabled'] === false && !$oUser->isBlocked() ) {
				$oStatus = self::disableUser( $oUser, $oPerformer, $oStatus );
				if ( !$oStatus->isGood() ) {
					return $oStatus;
				}
			} else if ( $aMetaData['enabled'] === true && $oUser->isBlocked() ) {
				$oStatus = self::enableUser( $oUser, $oPerformer, $oStatus );
				if ( !$oStatus->isGood() ) {
					return $oStatus;
				}
			}
		}

		$_SESSION['wsDomain'] = $tmpDomain;

		$oStatus = \Status::newGood( $oUser );

		$oUserManager =
			\MediaWiki\MediaWikiServices::getInstance()
			->getService( 'BSExtensionFactory' )
			->getExtension( 'BlueSpiceUserManager' );
		\Hooks::run(
			'BSUserManagerAfterAddUser',
			array(
				$oUserManager,
				$oUser,
				$aMetaData,
				&$oStatus,
				$oPerformer
			)
		);

		$ssUpdate = new \SiteStatsUpdate( 0, 0, 0, 0, 1 );
		$ssUpdate->doUpdate();

		return $oStatus;
	}
	/**
	 * Changes user password
	 * @param User $oUser
	 * @param array $aPassword
	 * @return Status
	 */
	 public static function editPassword( \User $oUser, $aPassword = array(), \User $oPerformer = null ) {
		 $oStatus = \Status::newGood();

		 if( !$oPerformer ) {
 			$oPerformer = \RequestContext::getMain()->getUser();
 		}

		$sPass = $aPassword['password'];

		if ( empty( $aPassword['password'] ) ) {
			$oNewStatus = \Status::newFatal( 'bs-usermanager-invalid-pwd' );
			$oStatus->merge( $oNewStatus );
			return $oStatus;
		}

		if ( !empty( $aPassword['password'] ) ) {
			if( !$oUser->isValidPassword( $sPass ) ) {
				$oNewStatus = \Status::newFatal( 'bs-usermanager-invalid-pwd' );
				$oStatus->merge( $oNewStatus );
			}
			if ( strtolower( $oUser->getName() ) == strtolower( $sPass ) ) {
				$oNewStatus = \Status::newFatal( 'password-name-match' );
				$oStatus->merge( $oNewStatus );
			}
			$sRePass = $aPassword['repassword'];
			if ( !isset($sRePass) || $sPass !== $sRePass ) {
				$oNewStatus = \Status::newFatal( 'badretype' );
				$oStatus->merge( $oNewStatus );
			}
		}

		if( !$oStatus->isOK() ) {
			return $oStatus;
		}

		$oUser->setPassword( $sPass );
		$oUser->saveSettings();

		return $oStatus;

	}
	/**
	 * Edits or adds an user
	 * @param User $oUser
	 * @param array $aMetaData
	 * @param boolean $bCreateIfNotExists
	 * @return Status
	 */
	public static function editUser( \User $oUser, $aMetaData = array(), $bCreateIfNotExists = false, \User $oPerformer = null ) {
		$oStatus = \Status::newGood();

		if( !$oPerformer ) {
			$oPerformer = \RequestContext::getMain()->getUser();
		}

		if( !empty($aMetaData['realname']) ) {
			if ( strpos( $aMetaData['realname'], '\\' ) ) {
				$oNewStatus = \Status::newFatal(
					'bs-usermanager-invalid-realname'
				);
				$oStatus->merge( $oNewStatus );
			}
		}
		if( !empty($aMetaData['email']) ) {
			if ( \Sanitizer::validateEmail( $aMetaData['email'] ) === false ) {
				$oNewStatus = \Status::newFatal(
					'bs-usermanager-invalid-email-gen'
				);
				$oStatus->merge( $oNewStatus );
			}
		}
		if( !$oStatus->isOK() ) {
			return $oStatus;
		}

		if( !empty($aMetaData['email']) ) {
			$oUser->setEmail( $aMetaData['email'] );
		} else {
			$oUser->setEmail('');
		}
		if( !empty($aMetaData['realname']) ) {
			$oUser->setRealName( $aMetaData['realname'] );
		} else {
			$oUser->setRealName('');
		}

		$oUser->saveSettings();

		if( isset( $aMetaData['enabled'] ) ) {
			if ( $aMetaData['enabled'] === false && !$oUser->isBlocked() ) {
				$oStatus = self::disableUser( $oUser, $oPerformer, $oStatus );
				if ( !$oStatus->isGood() ) {
					return $oStatus;
				}
			} else if ( $aMetaData['enabled'] === true && $oUser->isBlocked() ) {
				$oStatus = self::enableUser( $oUser, $oPerformer, $oStatus );
				if ( !$oStatus->isGood() ) {
					return $oStatus;
				}
			}
		}

		$oUserManager =
			\MediaWiki\MediaWikiServices::getInstance()
			->getService( 'BSExtensionFactory' )
			->getExtension( 'BlueSpiceUserManager' );
		\Hooks::run(
			'BSUserManagerAfterEditUser',
			array(
				$oUserManager,
				$oUser,
				$aMetaData,
				&$oStatus,
				$oPerformer,
			)
		);

		return \Status::newGood( $oUser );
	}

	/**
	 * Disables a user in the system.
	 * @param User $oUser The user to be disabled.
	 * @param User $oPerformer The user that requests the disabling
	 * @param Status $oStatus The status of the operation so far
	 * @return Status
	 */
	public static function disableUser( \User $oUser, \User $oPerformer, \Status &$oStatus = null ) {
		if ( is_null( $oStatus ) ) {
			$oStatus = \Status::newGood();
		}
		if ( $oUser->getId() == $oPerformer->getId() ) {
			$oStatus->setResult( false );
			$oStatus->fatal( 'bs-usermanager-no-self-block' );
			return $oStatus;
		}
		# Create block object.
		$block = new \Block();
		$block->setTarget( $oUser );
		$block->setBlocker( $oPerformer );
		$block->mReason = \wfMessage( 'bs-usermanager-log-user-disabled', $oUser->getName() )->text();
		$block->mExpiry = 'infinity';
		$block->prevents( 'createaccount', false );
		$block->prevents( 'editownusertalk', false );
		$block->prevents( 'sendemail', true );
		$block->isHardblock( true );
		$block->isAutoblocking( false );
		$reason = [ 'hookaborted' ];
		if ( !\Hooks::run( 'BlockIp', [ &$block, &$oPerformer, &$reason ] ) ) {
			$oStatus->setResult( false );
			$oStatus->fatal( $reason );
			return $oStatus;
		}

		# Try to insert block. Is there a conflicting block?
		$bStatus = $block->insert();
		if ( !$bStatus ) {
			$oStatus->setResult( false );
			$oStatus->fatal( 'bs-usermanager-block-error', $oUser->getName() );
		}
		return $oStatus;
	}

	/**
	 * Enables a disabled user
	 * @param User $oUser The user to be enabled
	 * @param User $oPerformer The user that requests the enabling
	 * @param Status $oStatus The status of the operation so far
	 * @return Status
	 */
	public static function enableUser( \User $oUser, \User $oPerformer, \Status &$oStatus = null ) {
		if ( is_null( $oStatus ) ) {
			$oStatus = \Status::newGood();
		}

		$block = \Block::newFromTarget( $oUser );
		$block->setBlocker( $oPerformer );
		$bStatus = $block->delete();
		if ( !$bStatus ) {
			$oStatus->setResult( false );
			$oStatus->fatal( 'bs-usermanager-unblock-error', $oUser->getName() );
		}
		return $oStatus;
	}

	/**
	 * Deletes an user form the database
	 * TODO: Merge into DeleteUser
	 * @param User $oUser
	 * @return Status
	 */
	public static function deleteUser( \User $oUser, \User $oPerformer = null ) {
		if ( $oUser->getId() == 0 ) {
			return \Status::newFatal( 'bs-usermanager-idnotexist' );
		}

		if ( $oUser->getId() == 1 ) {
			return \Status::newFatal( 'bs-usermanager-admin-nodelete' );
		}

		if( !$oPerformer ) {
			$oPerformer = \RequestContext::getMain()->getUser();
		}
		if ( $oUser->getId() == $oPerformer->getId() ) {
			return \Status::newFatal( 'bs-usermanager-self-nodelete' );
		}

		$oStatus = \Status::newGood( $oUser );
		$oUser->load( \User::READ_LATEST );
		if ( $oUser->getUserPage()->exists() ) {
			$oUserPageArticle = new \Article( $oUser->getUserPage() );
			$oUserPageArticle->doDelete( \wfMessage( 'bs-usermanager-db-error' )->plain() );
		}

		$dbw = \wfGetDB( \DB_MASTER );
		$res = $dbw->delete( 'user',
			array( 'user_id' => $oUser->getId() )
		);
		$res1 = $dbw->delete( 'user_groups',
			array( 'ug_user' => $oUser->getId() )
		);
		$res2 = $dbw->delete( 'user_newtalk',
			array( 'user_id' => $oUser->getId() )
		);
		$iUsers = $dbw->selectField( 'user', 'COUNT(*)', array() );
		$res3 = $dbw->update( 'site_stats',
			array( 'ss_users' => $iUsers ),
			array( 'ss_row_id' => 1 )
		);

		if ( ( $res === false ) || ( $res1 === false ) || ( $res2 === false ) || ( $res3 === false ) ) {
			$oStatus->merge( \Status::newFatal( 'bs-usermanager-db-error' ) );
		}

		$oUserManager =
			\MediaWiki\MediaWikiServices::getInstance()
			->getService( 'BSExtensionFactory' )
			->getExtension( 'BlueSpiceUserManager' );
		\Hooks::run( 'BSUserManagerAfterDeleteUser', array(
			$oUserManager,
			$oUser,
			&$oStatus,
			$oPerformer,
		));

		return $oStatus;
	}

	/**
	 * Removes / adds groups to a user
	 * See also https://www.mediawiki.org/wiki/Manual:$wgAddGroups
	 * @param User $oUser
	 * @param type $aGroups
	 * @return type
	 */
	public static function setGroups( \User $oUser, $aGroups = array() ) {
		$oLoggedInUser = \RequestContext::getMain()->getUser();
		$bAttemptChangeSelf = $oLoggedInUser->getId() == $oUser->getId();

		$bCheckDeSysop = $bAttemptChangeSelf
			&& in_array( 'sysop', $oLoggedInUser->getEffectiveGroups() )
			&& !in_array( 'sysop', $aGroups )
		;
		if ( $bCheckDeSysop ) {
			return \Status::newFatal( 'bs-usermanager-no-self-desysop' );
		}

		$aCurrentGroups = $oUser->getGroups();
		$aAddGroups = array_diff( $aGroups, $aCurrentGroups );
		$aRemoveGroups = array_diff( $aCurrentGroups, $aGroups );

		$aChangeableGroups = $oLoggedInUser->changeableGroups();

		foreach ( $aAddGroups as $sGroup ) {
			if ( in_array( $sGroup, self::$excludegroups ) ) {
				continue;
			}
			if ( !in_array( $sGroup, $aChangeableGroups['add'] ) ) {
				if ( !$bAttemptChangeSelf || !in_array( $sGroup, $aChangeableGroups['add-self'] ) ) {
					return \Status::newFatal( 'bs-usermanager-group-add-not-allowed', $sGroup );
				}
			}
			$oUser->addGroup( $sGroup );
		}
		foreach ( $aRemoveGroups as $sGroup ) {
			if ( in_array( $sGroup, self::$excludegroups ) ) {
				continue;
			}
			if ( !in_array( $sGroup, $aChangeableGroups['remove'] ) ) {
				if ( !$bAttemptChangeSelf || !in_array( $sGroup, $aChangeableGroups['remove-self'] ) ) {
					return \Status::newFatal( 'bs-usermanager-group-remove-not-allowed', $sGroup );
				}
			}
			$oUser->removeGroup( $sGroup );
		}

		$oStatus = \Status::newGood( $oUser );
		\Hooks::run( 'BSUserManagerAfterSetGroups', array(
			$oUser,
			$aGroups,
			$aAddGroups,
			$aRemoveGroups,
			self::$excludegroups,
			&$oStatus
		));

		$oUser->invalidateCache();
		return $oStatus;
	}

}