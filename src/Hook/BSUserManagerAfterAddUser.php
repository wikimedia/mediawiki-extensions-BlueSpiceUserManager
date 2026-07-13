<?php

namespace BlueSpice\UserManager\Hook;

use BlueSpice\Hook;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\Status\Status;
use MediaWiki\User\User;

/**
 * Located in \BlueSpice\UserManager\Extension::addUser after a user was initially added
 */
abstract class BSUserManagerAfterAddUser extends Hook {

	/**
	 * @var \UserManager
	 */
	protected $userManager = null;

	/**
	 * @var User
	 */
	protected $user = null;

	/**
	 * @var array
	 */
	protected $metaData = null;

	/**
	 * @var Status
	 */
	protected $status = null;

	/**
	 * @var User
	 */
	protected $performer = null;

	/**
	 * @param \UserManager $userManager
	 * @param User $user
	 * @param array $metaData
	 * @param Status &$status
	 * @param User $performer
	 * @return bool
	 */
	public static function callback( $userManager, $user, $metaData, &$status, $performer ) {
		$className = static::class;
		$hookHandler = new $className(
			null, null, $userManager, $user, $metaData, $status, $performer
		);
		return $hookHandler->process();
	}

	/**
	 * @param IContextSource $context
	 * @param Config $config
	 * @param \UserManager $userManager
	 * @param User $user
	 * @param array $metaData
	 * @param Status &$status
	 * @param User $performer
	 */
	public function __construct( $context, $config, $userManager, $user, $metaData,
		&$status, $performer ) {
		parent::__construct( $context, $config );

		$this->userManager = $userManager;
		$this->user = $user;
		$this->metaData = $metaData;
		$this->status = &$status;
		$this->performer = $performer;
	}

}
