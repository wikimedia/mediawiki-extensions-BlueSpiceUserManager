<?php

use BlueSpice\Special\ManagerBase;

class SpecialUserManager extends ManagerBase {

	public function __construct() {
		parent::__construct( 'UserManager', 'usermanager-viewspecialpage' );
	}

	/**
	 * @return string ID of the HTML element being added
	 */
	protected function getId() {
		return 'bs-usermanager-grid';
	}

	/**
	 * @return array
	 */
	protected function getModules() {
		return [
			'ext.bluespice.userManager'
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getJSVars() {
		return [
			'bsUserManagerForceResetLink' => $this->getConfig()->get( 'UserManagerForceResetLink' )
		];
	}
}
