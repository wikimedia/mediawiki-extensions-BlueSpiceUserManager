<?php

namespace BlueSpice\UserManager\BreadcrumbDataProvider;

use BlueSpice\Discovery\BreadcrumbDataProvider\BaseBreadcrumbDataProvider;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Title\Title;
use RuntimeException;

class SpecialUserManagerProvider extends BaseBreadcrumbDataProvider {

	private string $groupName;

	/**
	 * @param SpecialPageFactory $specialPageFactory
	 * @param TitleFactory $titleFactory
	 * @param MessageLocalizer $messageLocalizer
	 * @param WebRequestValues $webRequestValues
	 * @param NamespaceInfo $namespaceInfo
	 */
	public function __construct( private SpecialPageFactory $specialPageFactory,
		$titleFactory, $messageLocalizer, $webRequestValues, $namespaceInfo ) {
		parent::__construct( $titleFactory, $messageLocalizer, $webRequestValues, $namespaceInfo );

		$this->groupName = '';
	}

	/**
	 * @param Title $title
	 * @return Title
	 * @throws RuntimeException
	 */
	public function getRelevantTitle( $title ): Title {
		$specialPage = $this->specialPageFactory->getPage( 'UserManager' );
		if ( !$specialPage ) {
			throw new RuntimeException( 'The "UserManager" page doesn\'t exist' );
		}
		$specialPageTitle = $specialPage->getPageTitle();
		if ( !isset( $this->webRequestValues['group'] ) ) {
			return $specialPageTitle;
		}
		$groupTitle = $this->webRequestValues['group'];

		$this->groupName = $groupTitle;
		return $specialPageTitle;
	}

	/**
	 * @param Title $title
	 * @return array
	 */
	public function getLabels( $title ): array {
		$labels = [];
		if ( $this->groupName ) {
			$labels[] = [
				'text' => $this->groupName
			];
		}
		return $labels;
	}

	/**
	 *
	 * @param Title $title
	 * @return bool
	 */
	public function applies( Title $title ): bool {
		return $title->isSpecial( 'UserManager' );
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function isSelfLink( $node ): bool {
		if ( $this->groupName ) {
			return false;
		}
		return true;
	}
}
