<?php

namespace BlueSpice\UserManager\Tests;

use BlueSpice\UserManager\UserManager;
use InvalidArgumentException;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Permissions\Authority;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use PermissionsError;
use RuntimeException;

/**
 * @group Database
 */
class UserManagerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param bool $authorityIsAllowed
	 * @param string|null $expectedException
	 * @param string $username
	 * @param array $data
	 * @return void
	 * @dataProvider getUserCreationData
	 * @covers \BlueSpice\UserManager\UserManager::addUser
	 */
	public function testAddUser(
		bool $authorityIsAllowed, ?string $expectedException, string $username = 'Dummy', array $data = []
	) {
		[ $manager, $authority ] = $this->prepare( $authorityIsAllowed, $expectedException );

		$user = new User();
		$user->setName( $username );
		$manager->addUser( $user, $data, $authority );

		$user = $this->getServiceContainer()->getUserFactory()->newFromName( $username );
		$this->assertNotNull( $user );
		$this->assertEquals( $data['realName'], $user->getRealName() );
		$this->assertEquals( $data['email'], $user->getEmail() );
	}

	/**
	 * @param bool $authorityIsAllowed
	 * @param string|null $expectedException
	 * @param array $data
	 * @return void
	 * @dataProvider getUserEditData
	 * @covers \BlueSpice\UserManager\UserManager::updateUser
	 */
	public function testEditUser(
		bool $authorityIsAllowed, ?string $expectedException, array $data = []
	) {
		[ $manager, $authority ] = $this->prepare( $authorityIsAllowed, $expectedException );
		$user = $this->getTestUser()->getUser();
		$manager->updateUser( $user, $data, $authority );

		$user = $this->getServiceContainer()->getUserFactory()->newFromName( $user->getName() );
		$this->assertSame( $data['realName'], $user->getRealName() );
		$this->assertSame( $data['email'], $user->getEmail() );
	}

	/**
	 * @dataProvider provideGroupData
	 * @covers \BlueSpice\UserManager\UserManager::setGroups
	 * @return void
	 */
	public function testSetGroups( bool $authorityIsAllowed, ?string $expectedException = null, array $groups = [] ) {
		[ $manager, $authority ] = $this->prepare( $authorityIsAllowed, $expectedException );
		$user = $this->getTestUser( [ 'sysop', 'bureaucrat' ] )->getUser();

		if ( $expectedException ) {
			$this->expectException( $expectedException );
		}
		$manager->setGroups( $user, $groups, $authority );
		$groupManager = $this->getServiceContainer()->getUserGroupManager();
		$groupManager->clearCache( $user );
		$this->assertSame( $groups, $groupManager->getUserGroups( $user ) );
	}

	/**
	 * @covers \BlueSpice\UserManager\UserManager::blockUser
	 * @covers \BlueSpice\UserManager\UserManager::unblockUser
	 * @covers \BlueSpice\UserManager\UserManager::getBlock
	 * @return void
	 */
	public function testBlockUnblock() {
		[ $manager, $authority ] = $this->prepare( true, null );
		$user = $this->getTestUser()->getUser();
		$manager->blockUser( $user, $authority );
		$user = $this->getServiceContainer()->getUserFactory()->newFromName( $user->getName() );
		$this->assertTrue( $user->getBlock() !== null );
		$block = $manager->getBlock( $user );
		$this->assertInstanceOf( DatabaseBlock::class, $block );
		$manager->unblockUser( $block, $user, $authority );
		$user = $this->getServiceContainer()->getUserFactory()->newFromName( $user->getName() );
		$this->assertTrue( $user->getBlock() === null );
	}

	/**
	 * @param bool $authorityIsAllowed
	 * @param string|null $expectedException
	 * @param array $data
	 * @return void
	 * @covers \BlueSpice\UserManager\UserManager::resetPassword
	 * @dataProvider providePasswordData
	 */
	public function testResetPassword( bool $authorityIsAllowed, ?string $expectedException = null, array $data = [] ) {
		[ $manager, $authority ] = $this->prepare( $authorityIsAllowed, $expectedException );
		$user = $this->getTestUser()->getUser();

		if ( $expectedException ) {
			$this->expectException( $expectedException );
		}
		$manager->resetPassword( $user, $data, $authority );
		$this->assertTrue( true );
	}

	public function providePasswordData(): array {
		return [
			'no-permissions' => [
				'authorityIsAllowed' => false,
				'expectedException' => PermissionsError::class,
			],
			'invalid-pass' => [
				'authorityIsAllowed' => true,
				'expectedException' => InvalidArgumentException::class,
				'data' => [
					'password' => '123',
					'repassword' => '123',
				],
			],
			'no-retype' => [
				'authorityIsAllowed' => true,
				'expectedException' => InvalidArgumentException::class,
				'data' => [
					'password' => 'asd893&&asldkf7)',
				],
			],
			'valid' => [
				'authorityIsAllowed' => true,
				'expectedException' => null,
				'data' => [
					'password' => 'asd893&&asldkf7)',
					'repassword' => 'asd893&&asldkf7)',
				],
			],
		];
	}

	/**
	 * @return array[]
	 */
	public function provideGroupData(): array {
		return [
			'no-permissions' => [
				'authorityIsAllowed' => false,
				'expectedException' => PermissionsError::class,
			],
			'valid' => [
				'authorityIsAllowed' => true,
				'expectedException' => null,
				'groups' => [ 'sysop' ],
			],
			'remove-self-sysop' => [
				'authorityIsAllowed' => true,
				'expectedException' => RuntimeException::class,
				'groups' => [],
			],
			'add-non-existing-group' => [
				'authorityIsAllowed' => true,
				'expectedException' => RuntimeException::class,
				'groups' => [ 'random', 'sysop' ],
			],
		];
	}

	/**
	 * @return array[]
	 */
	public function getUserEditData() {
		return [
			'no-permissions' => [
				'authorityIsAllowed' => false,
				'expectedException' => PermissionsError::class,
			],
			'valid' => [
				'authorityIsAllowed' => true,
				'expectedException' => null,
				'data' => [
					'realName' => 'Test User',
					'email' => 'test@domain.com',
				],
			],
		];
	}

	/**
	 * @return array[]
	 */
	public function getUserCreationData() {
		return [
			'no-permissions' => [
				'authorityIsAllowed' => false,
				'expectedException' => PermissionsError::class,
			],
			'missing-params' => [
				'authorityIsAllowed' => true,
				'expectedException' => \InvalidArgumentException::class,
				'username' => 'TestUser',
				'data' => [
					'realName' => 'Test User',
				],
			],
			'valid' => [
				'authorityIsAllowed' => true,
				'expectedException' => null,
				'username' => 'TestUser',
				'data' => [
					'realName' => 'Test User',
					'email' => 'test@domain.com',
					'password' => 'asd893&&asldkf7)',
				],
			],
		];
	}

	/**
	 * @param bool $authorityIsAllowed
	 * @param string|null $expectedException
	 * @return array [ UserManager, Authority ]
	 */
	private function prepare( bool $authorityIsAllowed, ?string $expectedException ): array {
		/** @var UserManager $manager */
		$manager = $this->getServiceContainer()->getService( 'BlueSpice.UserManager.Manager' );

		if ( !$authorityIsAllowed ) {
			$authority = $this->createMock( Authority::class );
			$authority->method( 'isAllowedAll' )->willReturn( false );
		} else {
			$authority = $this->getTestSysop()->getAuthority();
		}
		if ( $expectedException ) {
			$this->expectException( $expectedException );
		}

		return [ $manager, $authority ];
	}
}
