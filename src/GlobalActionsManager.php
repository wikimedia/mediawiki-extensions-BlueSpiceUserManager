<?php

namespace BlueSpice\UserManager;

use Message;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\RestrictedTextLink;
use SpecialPage;

class GlobalActionsManager extends RestrictedTextLink {

	public function __construct() {
		parent::__construct( [] );
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return 'ga-bs-usermanager';
	}

	/**
	 * @return array
	 */
	public function getPermissions(): array {
		$permissions = [
			'usermanager-viewspecialpage',
			'usermanager-editpassword'
		];
		return $permissions;
	}

	/**
	 * @return string
	 */
	public function getHref(): string {
		$tool = SpecialPage::getTitleFor( 'UserManager' );
		return $tool->getLocalURL();
	}

	/**
	 * @return Message
	 */
	public function getText(): Message {
		return Message::newFromKey( 'bs-usermanager-label' );
	}

	/**
	 * @return Message
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'bs-usermanager-desc' );
	}

	/**
	 * @return Message
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'bs-usermanager-label' );
	}
}
