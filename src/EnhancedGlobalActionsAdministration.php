<?php

namespace BlueSpice\UserManager;

use HtmlArmor;
use MediaWiki\Html\Html;
use MediaWiki\Message\Message;

class EnhancedGlobalActionsAdministration extends GlobalActionsAdministration {

	/**
	 * @inheritDoc
	 */
	public function getPostHtml(): HtmlArmor {
		$html = Html::element( 'span', [
			'class' => 'badge rounded-pill text-bg-secondary'
		], Message::newFromKey( 'bs-usermanager-global-label' )->text() );
		return new HtmlArmor( $html );
	}
}
