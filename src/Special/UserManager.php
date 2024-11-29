<?php

namespace BlueSpice\UserManager\Special;

use Html;
use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use SpecialPage;

class UserManager extends SpecialPage {
	/** @var Config */
	private $config;

	/**
	 * @param ConfigFactory $configFactory
	 */
	public function __construct( ConfigFactory $configFactory ) {
		parent::__construct( 'UserManager', 'wikiadmin' );
		$this->config = $configFactory->makeConfig( 'bsg' );
	}

	/**
	 * @param string $subPage
	 * @return void
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );
		$this->getOutput()->addModules( 'ext.bluespice.userManager' );
		$this->getOutput()->addHTML(
			Html::element( 'div', [ 'id' => 'bs-usermanager-grid' ] )
		);
		$this->getOutput()->addJsConfigVars( [
			'bsUserManagerForceResetLink' => $this->config->get( 'UserManagerForceResetLink' ),
			'bsUserManagerPermissions' => [
				'editpassword' => $this->getUser()->isAllowedAll( 'userrights', 'usermanager-editpassword' ),
				'usergroups' => $this->getUser()->isAllowedAll( 'userrights', 'wikiadmin' ),
			]
		] );
	}
}
