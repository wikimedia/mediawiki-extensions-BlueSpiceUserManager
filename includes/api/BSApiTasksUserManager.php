<?php
/**
 * Provides the user manager tasks api for BlueSpice.
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
 * For further information visit https://bluespice.com
 *
 * @author     Patric Wirth
 * @package    Bluespice_Extensions
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 */

/**
 * UserManager Api class
 * @package BlueSpice_Extensions
 */
class BSApiTasksUserManager extends BSApiTasksBase {

	/**
	 * Methods that can be called by task param
	 * @var array
	 */
	protected $aTasks = [
		'addUser' => [
			'examples' => [
				[
					'userName' => 'someUserName',
					'realname' => 'Some User',
					'email' => 'user@example.com',
					'password' => 'pass1234',
					'rePassword' => 'pass1234',
					'enabled' => true,
					'groups' => [ 'sysop', 'bot' ]
				]
			],
			'params' => [
				'userName' => [
					'desc' => '',
					'type' => 'string',
					'required' => true
				],
				'realname' => [
					'desc' => '',
					'type' => 'string',
					'required' => false
				],
				'email' => [
					'desc' => '',
					'type' => 'string',
					'required' => false
				],
				'password' => [
					'desc' => '',
					'type' => 'string',
					'required' => false
				],
				'rePassword' => [
					'desc' => 'Required if password param is passed',
					'type' => 'string',
					'required' => false
				],
				'enabled' => [
					'desc' => 'Is user enabled',
					'type' => 'boolean',
					'required' => false
				],
				'groups' => [
					'desc' => 'Array of valid group names',
					'type' => 'array',
					'required' => false
				]
			]
		],
		'editUser' => [
			'examples' => [
				[
					'userID' => 15,
					'realname' => 'Some User',
					'email' => 'user@example.com',
					'enabled' => true
				]
			],
			'params' => [
				'userID' => [
					'desc' => 'Valid User ID',
					'type' => 'integer',
					'required' => true
				],
				'realname' => [
					'desc' => '',
					'type' => 'string',
					'required' => false
				],
				'email' => [
					'desc' => '',
					'type' => 'string',
					'required' => false
				],
				'enabled' => [
					'desc' => 'Is user enabled',
					'type' => 'boolean',
					'required' => false
				]
			]
		],
		'deleteUser' => [
			'examples' => [
				[
					'userIDs' => [ 12, 23, 22 ]
				]
			],
			'params' => [
				'userIDs' => [
					'desc' => 'Array of valid User IDs',
					'type' => 'array',
					'required' => true
				]
			]
		],
		'disableUser' => [
			'examples' => [
				[
					'userID' => 12
				]
			],
			'params' => [
				'userID' => [
					'desc' => 'Valid User ID',
					'type' => 'integer',
					'required' => true
				]
			]
		],
		'enableUser' => [
			'examples' => [
				[
					'userID' => 12
				]
			],
			'params' => [
				'userID' => [
					'desc' => 'Valid User ID',
					'type' => 'integer',
					'required' => true
				]
			]
		],
		'setUserGroups' => [
			'examples' => [
				[
					'userIDs' => [ 12 ],
					'groups' => [ 'sysop', 'bot' ]
				]
			],
			'params' => [
				'userIDs' => [
					'desc' => 'Array of valid User IDs',
					'type' => 'array',
					'required' => true
				],
				'groups' => [
					'desc' => 'Array of valid group names',
					'type' => 'array',
					'required' => true
				]
			]
		],
		'editPassword' => [
			'examples' => [
				[
					'userID' => 12,
					'password' => 'new1234',
					'rePassword' => 'new1234',
					'strategy' => 'password'
				]
			],
			'params' => [
				'userID' => [
					'desc' => 'Valid User ID',
					'type' => 'integer',
					'required' => true
				],
				'password' => [
					'desc' => '',
					'type' => 'string',
					'required' => false
				],
				'rePassword' => [
					'desc' => '',
					'type' => 'string',
					'required' => false
				],
				'strategy' => [
					'desc' => 'Type of reset to perform. "reset" or "password"',
					'type' => 'string',
					'required' => true
				]
			]
		]
	];

	/**
	 * Returns an array of tasks and their required permissions
	 * array( 'taskname' => array('read', 'edit') )
	 * @return array
	 */
	protected function getRequiredTaskPermissions() {
		return [
			'addUser' => [ 'wikiadmin' ],
			'editUser' => [ 'wikiadmin' ],
			'disableUser' => [ 'wikiadmin' ],
			'enableUser' => [ 'wikiadmin' ],
			'deleteUser' => [ 'wikiadmin', 'usermanager-deleteuser' ],
			'setUserGroups' => [ 'userrights' ],
			'editPassword' => [ 'wikiadmin', 'usermanager-editpassword' ]
		];
	}

	/**
	 *
	 * @return bool
	 */
	public function getTaskDataDefinitions() {
		// TODO
		return false;
	}

	/**
	 * Creates an user.
	 * @param stdClass $oTaskData
	 * @return stdClass Standard tasks API return
	 */
	protected function task_addUser( $oTaskData ) {
		$oReturn = $this->makeStandardReturn();
		$aGroups = false;
		if ( isset( $oTaskData->groups ) ) {
			$aGroups = $oTaskData->groups;
		}

		if ( empty( $oTaskData->userName ) ) {
			$oReturn->message = wfMessage(
				'bs-usermanager-invalid-uname'
			)->plain();
		}

		$aMetaData = [];
		if ( isset( $oTaskData->password ) ) {
			$aMetaData['password'] = $oTaskData->password;
		}
		if ( isset( $oTaskData->rePassword ) ) {
			$aMetaData['repassword'] = $oTaskData->rePassword;
		}
		if ( isset( $oTaskData->email ) ) {
			$aMetaData['email'] = $oTaskData->email;
		}
		if ( isset( $oTaskData->realname ) ) {
			$aMetaData['realname'] = $oTaskData->realname;
		}
		if ( isset( $oTaskData->enabled ) ) {
			$aMetaData['enabled'] = $oTaskData->enabled;
		}

		$oStatus = \BlueSpice\UserManager\Extension::addUser(
			$oTaskData->userName,
			$aMetaData,
			$this->getUser()
		);
		if ( !$oStatus->isOK() ) {
			$oReturn->message = $oStatus->getMessage()->parse();
			return $oReturn;
		}

		if ( is_array( $aGroups ) ) {
			$oStatus = \BlueSpice\UserManager\Extension::setGroups(
				$oStatus->getValue(),
				$aGroups
			);
			if ( !$oStatus->isOK() ) {
				$oReturn->message = $oStatus->getMessage()->parse();
				return $oReturn;
			}
		}

		$oReturn->success = true;
		$oReturn->message = wfMessage( 'bs-usermanager-user-added' )->plain();

		return $oReturn;
	}

	/**
	 * Changes password of a user.
	 * @param stdClass $oTaskData
	 * @return stdClass Standard tasks API return
	 */
	protected function task_editPassword( $oTaskData ) {
		$oReturn = $this->makeStandardReturn();

		$data = [
			'strategy' => $oTaskData->strategy
		];
		if ( $data['strategy'] === 'password' ) {
			$data['password'] = $oTaskData->password;
			$data['repassword'] = $oTaskData->rePassword;
		}

		$oUser = $this->services->getUserFactory()->newFromID( $oTaskData->userID );

		$oStatus = \BlueSpice\UserManager\Extension::editPassword(
			$oUser,
			$data,
			$this->getUser()
		);

		if ( !$oStatus->isOK() ) {
			$oReturn->message = $oStatus->getMessage()->parse();
			return $oReturn;
		}

		$oReturn->success = true;
		$oReturn->message = wfMessage(
			'bs-usermanager-editpassword-successful'
		)->plain();

		return $oReturn;
	}

	/**
	 * Edits an user.
	 * @param stdClass $oTaskData
	 * @return stdClass Standard tasks API return
	 */
	protected function task_editUser( $oTaskData ) {
		$oReturn = $this->makeStandardReturn();

		if ( empty( $oTaskData->userID ) ) {
			$oReturn->message = wfMessage(
				'bs-usermanager-invalid-uname'
			)->plain();
		}
		$oUser = $this->services->getUserFactory()->newFromID( $oTaskData->userID );

		$aMetaData = [];

		if ( isset( $oTaskData->email ) ) {
			$aMetaData['email'] = $oTaskData->email;
		}
		if ( isset( $oTaskData->realname ) ) {
			$aMetaData['realname'] = $oTaskData->realname;
		}
		if ( isset( $oTaskData->enabled ) ) {
			$aMetaData['enabled'] = $oTaskData->enabled;
		}

		$oStatus = \BlueSpice\UserManager\Extension::editUser(
			$oUser,
			$aMetaData,
			true,
			$this->getUser()
		);

		if ( !$oStatus->isOK() ) {
			$oReturn->message = $oStatus->getMessage()->parse();
			return $oReturn;
		}

		$oReturn->success = true;
		$oReturn->message = wfMessage(
			'bs-usermanager-save-successful'
		)->plain();

		return $oReturn;
	}

	/**
	 * Deletes an User.
	 * @param stdClass $oTaskData
	 * @return stdClass Standard tasks API return
	 */
	protected function task_deleteUser( $oTaskData ) {
		$oReturn = $this->makeStandardReturn();

		$userFactory = $this->services->getUserFactory();
		$userOptionsLookup = $this->services->getUserOptionsLookup();
		foreach ( $oTaskData->userIDs as $sUserID ) {
			$oUser = $userFactory->newFromID( $sUserID );
			$oStatus = \BlueSpice\UserManager\Extension::deleteUser( $oUser, $this->getUser() );
			if ( !$oStatus->isOK() ) {
				$oReturn->message = $oStatus->getMessage()->parse();
				return $oReturn;
			}
		}

		$oReturn->success = true;
		$msg = Message::newFromKey( 'bs-usermanager-user-deleted' );
		$idCount = count( $oTaskData->userIDs );
		$msg->params( $idCount );
		if ( $idCount === 1 ) {
			$msg->params(
				$userOptionsLookup->getOption(
					$userFactory->newFromID( $oTaskData->userIDs[0] ),
					'gender'
				)
			);
		}
		$oReturn->message = $msg->text();

		return $oReturn;
	}

	/**
	 * Disables a user.
	 * @param stdClass $oTaskData
	 * @return stdClass Standard tasks API return
	 */
	protected function task_disableUser( $oTaskData ) {
		$oReturn = $this->makeStandardReturn();

		$oUser = $this->services->getUserFactory()->newFromID( $oTaskData->userID );

		$oPerformer = $this->getUser();
		$oStatus = \BlueSpice\UserManager\Extension::disableUser( $oUser, $oPerformer );
		if ( !$oStatus->isOK() ) {
			$oReturn->message = $oStatus->getMessage()->parse();
			return $oReturn;
		}

		$oReturn->success = true;
		$oReturn->message = wfMessage( 'bs-usermanager-user-disabled', $oUser->getName() )->text();

		return $oReturn;
	}

	/**
	 * Enables an User.
	 * @param stdClass $oTaskData
	 * @return \BlueSpice\Api\Response\Standard Standard tasks API return
	 */
	protected function task_enableUser( $oTaskData ) {
		$oReturn = $this->makeStandardReturn();

		$oUser = $this->services->getUserFactory()->newFromID( $oTaskData->userID );

		$oPerformer = $this->getUser();
		$oStatus = \BlueSpice\UserManager\Extension::enableUser( $oUser, $oPerformer );
		if ( !$oStatus->isOK() ) {
			$oReturn->message = $oStatus->getMessage()->parse();
			return $oReturn;
		}

		$oReturn->success = true;
		$oReturn->message = wfMessage( 'bs-usermanager-user-enabled', $oUser->getName() )->text();

		return $oReturn;
	}

	/**
	 * Sets user groups for user.
	 * @param stdClass $oTaskData
	 * @return stdClass Standard tasks API return
	 */
	protected function task_setUserGroups( $oTaskData ) {
		$oReturn = $this->makeStandardReturn();

		if ( empty( $oTaskData->userIDs ) || !is_array( $oTaskData->userIDs ) ) {
			$oReturn->message = wfMessage(
				'bs-usermanager-invalid-uname'
			)->plain();
		}

		if ( !isset( $oTaskData->groups ) || !is_array( $oTaskData->groups ) ) {
			$oReturn->message = wfMessage(
				'bs-usermanager-invalid-groups'
			)->plain();
		}
		$oStatus = Status::newGood();
		$userFactory = $this->services->getUserFactory();
		foreach ( $oTaskData->userIDs as $sUserID ) {
			$oUser = $userFactory->newFromID( $sUserID );
			$oStatus->merge( \BlueSpice\UserManager\Extension::setGroups(
				$oUser,
				$oTaskData->groups
			) );
		}

		if ( !$oStatus->isOK() ) {
			$oReturn->message = $oStatus->getMessage()->parse();
			return $oReturn;
		}

		$oReturn->success = true;
		$oReturn->message = wfMessage(
			'bs-usermanager-save-successful'
		)->plain();

		return $oReturn;
	}
}
