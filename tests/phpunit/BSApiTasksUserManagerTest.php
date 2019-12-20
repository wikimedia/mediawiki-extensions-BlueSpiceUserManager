<?php

use BlueSpice\Tests\BSApiTasksTestBase;

/**
 * @group Broken
 * @group large
 * @group API
 * @group BlueSpice
 * @group BlueSpiceExtensions
 * @group BlueSpiceUserManager
 * @group Database
 */
class BSApiTasksUserManagerTest extends BSApiTasksTestBase {

	/**
	 *
	 * @return string
	 */
	protected function getModuleName() {
		return 'bs-usermanager-tasks';
	}

	/**
	 *
	 * @return array
	 */
	public function getTokens() {
		return $this->getTokenList( self::$users[ 'sysop' ] );
	}

	/**
	 * @covers \BSApiTasksUserManager::task_addUser
	 */
	public function testAddUser() {
		$data = $this->executeTask( 'addUser', [
			'userName' => 'SomeName',
			'realname' => 'Some Name',
			'password' => 'pass123',
			'rePassword' => 'pass123',
			'email' => 'example@localhost.com',
			'enabled' => true,
			'groups' => [ 'sysop' ]
		] );

		$this->assertEquals( true, $data->success );

		$this->assertSelect(
			'user',
			[ 'user_name', 'user_real_name', 'user_email', ],
			[ "user_name = 'SomeName'" ],
			[ [ 'SomeName', 'Some Name', 'example@localhost.com' ] ]
		);
	}

	/**
	 * @covers \BSApiTasksUserManager::task_editUser
	 */
	public function testEditUser() {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask( 'editUser', [
			'userID' => $userId,
			'realname' => 'Some Other Name',
			'password' => 'pass123',
			'rePassword' => 'pass123',
			'email' => 'example@localhost.com',
			'enabled' => true,
			'groups' => [ 'bureaucrat' ]
		] );

		$this->assertEquals( true, $data->success );

		$this->assertSelect(
			'user',
			[ 'user_real_name' ],
			[ "user_id = '" . $userId . "'" ],
			[ [ 'Some Other Name' ] ]
		);
	}

	/**
	 * @covers \BSApiTasksUserManager::task_disableUser
	 */
	public function testDisableUser() {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask( 'disableUser', [
			'userID' => $userId
		] );

		$this->assertEquals( true, $data->success );

		$this->assertTrue( $this->userIsBlocked( $userId ) );
	}

	/**
	 * @covers \BSApiTasksUserManager::task_enableUser
	 */
	public function testEnableUser() {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask( 'enableUser', [
			'userID' => $userId
		] );

		$this->assertEquals( true, $data->success );

		$this->assertFalse( $this->userIsBlocked( $userId ) );
	}

	/**
	 * @covers \BSApiTasksUserManager::task_deleteUser
	 */
	public function testDeleteUser() {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask( 'deleteUser', [
			'userIDs' => [ $userId ]
		] );

		$this->assertEquals( true, $data->success );

		$this->assertFalse( $this->existsInDb( $userId ) );
	}

	/**
	 * @covers \BSApiTasksUserManager::task_setUserGroups
	 */
	public function testSetUserGroups() {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask( 'addUser', [
			'userIDs' => [ $userId ],
			'groups' => [ 'bot' ]
		] );

		$this->assertEquals( true, $data->success );

		$this->assertSelect(
			'user_groups',
			[ 'ug_group' ],
			[ "ug_user = '" . $userId . "'" ],
			[ [ 'bot' ] ]
		);
	}

	/**
	 * @covers \BSApiTasksUserManager::task_editPassword
	 */
	public function testEditPassword() {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask( 'addUser', [
			'userID' => $userId,
			'password' => 'pass1234',
			'rePassword' => 'pass1234'
		] );

		$this->assertEquals( true, $data->success );
	}

	/**
	 *
	 * @param int $iId
	 * @return bool
	 */
	protected function userIsBlocked( $iId ) {
		$db = wfGetDB( DB_REPLICA );
		$res = $db->select( 'ipblocks', [ 'ipb_user' ], [ 'ipb_user = ' . $iId ], wfGetCaller() );
		if ( $res->numRows() === 0 ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 *
	 * @param int $iId
	 * @return bool
	 */
	protected function existsInDb( $iId ) {
		$db = wfGetDB( DB_REPLICA );
		$res = $db->select( 'user', [ 'user_id' ], [ 'user_id = ' . $iId ], wfGetCaller() );
		if ( $res->numRows() === 0 ) {
			return false;
		} else {
			return true;
		}
	}
}
