<?php

class SpecialUserManager extends \BlueSpice\SpecialPage {

	public function __construct() {
		parent::__construct( 'UserManager', 'usermanager-viewspecialpage' );
	}

	/**
	 *
	 * @global OutputPage $this->getOutput()
	 * @param string $parameter URL parameters to special page
	 */
	public function execute( $parameter ) {
		parent::execute( $parameter );
		$this->getOutput()->addModules( 'ext.bluespice.userManager' );
		$this->getOutput()->addHTML(
			Html::element( 'div',
				[ 'id' => 'bs-usermanager-grid', 'class' => 'bs-manager-container' ] )
		);
	}

}
