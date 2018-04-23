<?php

namespace BlueSpice\UserManager;

use BlueSpice\IAdminTool;

class AdminTool implements IAdminTool {

	public function getURL() {
		$tool = \SpecialPage::getTitleFor( 'UserManager' );
		return $tool->getLocalURL();
	}

	public function getDescription() {
		return wfMessage( 'bs-usermanager-desc' );
	}

	public function getName() {
		return wfMessage( 'bs-usermanager-label' );
	}

	public function getClasses() {
		$classes = array(
			'bs-icon-user-add'
		);

		return $classes;
	}

	public function getDataAttributes() {
		return [];
	}

	public function getPermissions() {
		$permissions = array(
			'usermanager-viewspecialpage',
			'usermanager-editpassword'
		);
		return $permissions;
	}

}