<?php

/**
 * @group medium
 * @group API
 * @group BlueSpice
 * @group BlueSpiceExtensions
 * @group BlueSpiceUserManager
 */
class BSApiChangeableGroupStoreTest extends BSApiExtJSStoreTestBase {

	protected $iFixtureTotal = 4;

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
		return;
	}

	protected function setUp() {
		parent::setUp();
		$this->mergeMwGlobalArrayValue(
			'wgGroupPermissions',
			[ 'groupchanger' => [ 'userrights' => false ] ]
		);
		$aChangeableGroups = [ 'autoreview', 'bot', 'bureaucrat', 'sysop' ];
		$this->setMwGlobals( [
			'wgAddGroups' => [ 'groupchanger' => $aChangeableGroups ],
			'wgRemoveGroups' => [ 'groupchanger' => $aChangeableGroups ],
			'wgGroupsAddToSelf' => [ 'groupchanger' => $aChangeableGroups ],
			'wgGroupsRemoveFromSelf' => [ 'groupchanger' => $aChangeableGroups ]
		] );
		$this->doLogin( "uploader" );
		global $wgUser;
		$wgUser->addGroup( "groupchanger" );
	}

	public function provideSingleFilterData() {
		return [
			'Filter by group_name' => [ 'string', 'ct', 'group_name', 'auto', 1 ],
			'Filter by additional_group' => ['boolean', 'eq', 'additional_group', false, 4]
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

	protected function getModuleName() {
		return 'bs-usermanager-group-store';
	}
}
