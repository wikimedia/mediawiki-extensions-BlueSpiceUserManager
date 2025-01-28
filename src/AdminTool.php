<?php

namespace BlueSpice\UserManager;

use BlueSpice\IAdminTool;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPage;

class AdminTool implements IAdminTool {

	/**
	 *
	 * @return string String of the URL
	 */
	public function getURL() {
		$tool = SpecialPage::getTitleFor( 'UserManager' );
		return $tool->getLocalURL();
	}

	/**
	 *
	 * @return Message
	 */
	public function getDescription() {
		return wfMessage( 'bs-usermanager-desc' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getName() {
		return wfMessage( 'bs-usermanager-label' );
	}

	/**
	 *
	 * @return array
	 */
	public function getClasses() {
		$classes = [
			'bs-icon-user-add'
		];

		return $classes;
	}

	/**
	 *
	 * @return array
	 */
	public function getDataAttributes() {
		return [];
	}

	/**
	 *
	 * @return array
	 */
	public function getPermissions() {
		$permissions = [
			'usermanager-viewspecialpage',
			'usermanager-editpassword'
		];
		return $permissions;
	}

}
