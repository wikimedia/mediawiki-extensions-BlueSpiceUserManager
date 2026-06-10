<?php

namespace BlueSpice\UserManager;

use HtmlArmor;
use MediaWiki\Html\Html;
use MediaWiki\Message\Message;
use MediaWiki\Title\TitleFactory;

class EnhancedGlobalActionsAdministration extends GlobalActionsAdministration {

	/**
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly TitleFactory $titleFactory
	) {
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function getPostHtml(): HtmlArmor {
		$html = Html::element( 'span', [
			'class' => 'badge rounded-pill text-bg-secondary'
		], Message::newFromKey( 'bs-usermanager-global-label' )->text() );
		return new HtmlArmor( $html );
	}

	/**
	 * @return string
	 */
	public function getHref(): string {
		if ( defined( 'FARMER_IS_ROOT_WIKI_CALL' ) && FARMER_IS_ROOT_WIKI_CALL ) {
			$title = $this->titleFactory->makeTitle( NS_SPECIAL, 'UserManager' );
			return $title->getLocalURL();
		}
		$title = $this->titleFactory->newFromText( 'w:Special:UserManager' );
		return $title->getFullURL();
	}
}
