<?php

use BlueSpice\Tests\BSApiExtJSStoreTestBase;
use MediaWiki\MediaWikiServices;

/**
 * @group medium
 * @group API
 * @group BlueSpice
 * @group BlueSpiceExtensions
 * @group BlueSpiceUserManager
 * @group Database
 * @covers BSApiChangeableGroupStore
 */
class BSApiChangeableGroupStoreTest extends BSApiExtJSStoreTestBase {

	protected $iFixtureTotal = 3;

	protected function getStoreSchema() {
		return [
			'group_name' => [
				'type' => 'string'
			],
			'additional_group' => [
				'type' => 'boolean'
			],
			'displayname' => [
				'type' => 'string'
			]
		];
	}

	protected function createStoreFixtureData() {
	}

	protected function setUp(): void {
		parent::setUp();
		$this->mergeMwGlobalArrayValue(
			'wgGroupPermissions',
			[ 'groupchanger' => [ 'userrights' => false ] ]
		);
		$aChangeableGroups = [ 'bot', 'bureaucrat', 'sysop' ];
		$this->setMwGlobals( [
			'wgAddGroups' => [ 'groupchanger' => $aChangeableGroups ],
			'wgRemoveGroups' => [ 'groupchanger' => $aChangeableGroups ],
			'wgGroupsAddToSelf' => [ 'groupchanger' => $aChangeableGroups ],
			'wgGroupsRemoveFromSelf' => [ 'groupchanger' => $aChangeableGroups ]
		] );
		$userGroupManager = MediaWikiServices::getInstance()->getUserGroupManager();
		$user = $this->getTestUser( 'uploader' )->getUser();
		$userGroupManager->addUserToGroup( $user, 'groupchanger' );
	}

	public function provideSingleFilterData() {
		return [
			'Filter by group_name' => [ 'string', 'ct', 'group_name', 'sys', 1 ],
			'Filter by additional_group' => [ 'boolean', 'eq', 'additional_group', false, 6 ]
		];
	}

	public function provideMultipleFilterData() {
		return [
			'Filter by group_name and displayname' => [
				[
					[
						'type' => 'string',
						'comparison' => 'eq',
						'field' => 'group_name',
						'value' => 'bureaucrat'
					],
					[
						'type' => 'string',
						'comparison' => 'ct',
						'field' => 'displayname',
						'value' => 'Bur'
					]
				],
				1
			]
		];
	}

	public function provideKeyItemData() {
		return [
			'bot' => [ "group_name", "bot" ],
			'bureaucrat' => [ "group_name", "bureaucrat" ],
			'sysop' => [ "group_name", "sysop" ]
		];
	}

	protected function getModuleName() {
		return 'bs-usermanager-group-store';
	}

	protected function skipAssertTotal() {
		return true;
	}
}
