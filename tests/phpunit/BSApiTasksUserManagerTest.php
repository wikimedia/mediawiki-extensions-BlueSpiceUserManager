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

	protected function getModuleName() {
		return 'bs-usermanager-tasks';
	}

	public function getTokens() {
		return $this->getTokenList( self::$users[ 'sysop' ] );
	}

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

	public function testDisableUser() {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask( 'disableUser', [
			'userID' => $userId
		] );

		$this->assertEquals( true, $data->success );

		$this->assertTrue( $this->userIsBlocked( $userId ) );
	}

	public function testEnableUser() {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask( 'enableUser', [
			'userID' => $userId
		] );

		$this->assertEquals( true, $data->success );

		$this->assertFalse( $this->userIsBlocked( $userId ) );
	}

	public function testDeleteUser() {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask( 'deleteUser', [
			'userIDs' => [ $userId ]
		] );

		$this->assertEquals( true, $data->success );

		$this->assertFalse( $this->existsInDb( $userId ) );
	}

	public function setUserGroups() {
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

	public function editPassword() {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask( 'addUser', [
			'userID' => $userId,
			'password' => 'pass1234',
			'rePassword' => 'pass1234'
		] );

		$this->assertEquals( true, $data->success );
	}

	protected function userIsBlocked( $iId ) {
		$db = wfGetDB( DB_REPLICA );
		$res = $db->select( 'ipblocks', [ 'ipb_user' ], [ 'ipb_user = ' . $iId ], wfGetCaller() );
		if ( $res->numRows() === 0 ) {
			return false;
		} else {
			return true;
		}
	}

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
