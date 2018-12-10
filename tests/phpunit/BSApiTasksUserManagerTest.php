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

	protected function getModuleName () {
		return 'bs-usermanager-tasks';
	}

	function getTokens () {
		return $this->getTokenList ( self::$users[ 'sysop' ] );
	}

	public function testAddUser () {
		$data = $this->executeTask ( 'addUser', array (
			'userName' => 'SomeName',
			'realname' => 'Some Name',
			'password' => 'pass123',
			'rePassword' => 'pass123',
			'email' => 'example@localhost.com',
			'enabled' => true,
			'groups' => array ( 'sysop' )
		) );

		$this->assertEquals ( true, $data->success );

		$this->assertSelect(
			'user',
			array( 'user_name', 'user_real_name', 'user_email',  ),
			array( "user_name = 'SomeName'" ),
			array(  array( 'SomeName', 'Some Name', 'example@localhost.com' )  )
		);
	}

	public function testEditUser () {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask ( 'editUser', array (
			'userID' => $userId,
			'realname' => 'Some Other Name',
			'password' => 'pass123',
			'rePassword' => 'pass123',
			'email' => 'example@localhost.com',
			'enabled' => true,
			'groups' => array ( 'bureaucrat' )
		) );

		$this->assertEquals ( true, $data->success );

		$this->assertSelect(
			'user',
			array( 'user_real_name'),
			array( "user_id = '" . $userId . "'" ),
			array( array( 'Some Other Name' ) )
		);
	}

	public function testDisableUser () {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask ( 'disableUser', array (
			'userID' => $userId
		) );

		$this->assertEquals ( true, $data->success );

		$this->assertTrue( $this->userIsBlocked( $userId ) );
	}

	public function testEnableUser () {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask ( 'enableUser', array (
			'userID' => $userId
		) );

		$this->assertEquals ( true, $data->success );

		$this->assertFalse( $this->userIsBlocked( $userId ) );
	}

	public function testDeleteUser () {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask ( 'deleteUser', array (
			'userIDs' => [ $userId ]
		) );

		$this->assertEquals ( true, $data->success );

		$this->assertFalse( $this->existsInDb( $userId ) );
	}

	public function setUserGroups () {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask ( 'addUser', array (
			'userIDs' => [ $userId ],
			'groups' => array ( 'bot' )
		) );

		$this->assertEquals ( true, $data->success );

		$this->assertSelect(
			'user_groups',
			array( 'ug_group'),
			array( "ug_user = '" . $userId . "'" ),
			array( array( 'bot' ) )
		);
	}

	public function editPassword () {
		$userId = self::$users[ 'uploader' ]->getUser()->getId();
		$data = $this->executeTask ( 'addUser', array (
			'userID' => $userId,
			'password' => 'pass1234',
			'rePassword' => 'pass1234'
		) );

		$this->assertEquals ( true, $data->success );
	}

	protected function userIsBlocked( $iId ) {
		$db = wfGetDB( DB_REPLICA );
		$res = $db->select( 'ipblocks', array( 'ipb_user' ), array( 'ipb_user = ' . $iId ), wfGetCaller() );
		if( $res->numRows() === 0 ) {
			return false;
		} else {
			return true;
		}
	}

	protected function existsInDb( $iId ) {
		$db = wfGetDB( DB_REPLICA );
		$res = $db->select( 'user', array( 'user_id' ), array( 'user_id = ' . $iId ), wfGetCaller() );
		if( $res->numRows() === 0 ) {
			return false;
		} else {
			return true;
		}
	}
}
