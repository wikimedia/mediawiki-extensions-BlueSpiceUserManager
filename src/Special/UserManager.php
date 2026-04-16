<?php

namespace BlueSpice\UserManager\Special;

use BlueSpice\UserManager\GroupManager;
use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Context\RequestContext;
use MediaWiki\Html\Html;
use OOJSPlus\Special\OOJSGridSpecialPage;
use OOUI\MessageWidget;

class UserManager extends OOJSGridSpecialPage {

	/** @var Config */
	private $config;

	/**
	 * @param ConfigFactory $configFactory
	 */
	public function __construct( ConfigFactory $configFactory, private readonly GroupManager $groupManager ) {
		parent::__construct( 'UserManager' );
		$this->config = $configFactory->makeConfig( 'bsg' );
	}

	/** @inheritDoc */
	public function getRestriction(): string {
		return 'wikiadmin';
	}

	/**
	 * @param string $subPage
	 * @return void
	 */
	public function execute( $subPage ) {
		$this->getOutput()->addModules( [ 'ext.bluespice.userManager' ] );
		$request = RequestContext::getMain()->getRequest();
		if ( $request->getVal( 'group' ) ) {
			$groupName = $request->getVal( 'group' );
			$this->outputTeam( $groupName );
			parent::execute( $groupName );
			$this->getOutput()->setPageTitle( $groupName );
			return;
		}

		parent::execute( $subPage );
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

	/**
	 * @param string $groupName
	 * @return void
	 */
	protected function outputTeam( string $groupName ) {
		try {
			$group = $this->groupManager->assertGroupExists( $groupName );
		} catch ( \Throwable $ex ) {
			$this->getOutput()->enableOOUI();
			$this->getOutput()->addHTML(
				new MessageWidget(
					[
						'type' => 'error',
						'label' => $this->msg( 'bs-usermanager-group-not-found', $groupName )->text()
					]
				)
			);
			return;
		}

		$this->getOutput()->addHTML(
			Html::element( 'div', [
				'id' => 'bs-usermanager-group-details',
				'data-group' => $groupName
			] )
		);
	}
}
