<?php

namespace BlueSpice\UserManager\Hook;

use BlueSpice\Hook;
use BlueSpice\UserManager\Extension as UserManager;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\Status\Status;
use MediaWiki\User\User;

/**
 * Located in \BlueSpice\UserManager\Extension::deleteUser after a user was deleted
 */
abstract class BSUserManagerAfterDeleteUser extends Hook {

	/**
	 * @var UserManager
	 */
	protected $userManager = null;

	/**
	 * @var User
	 */
	protected $user = null;

	/**
	 * @var Status
	 */
	protected $status = null;

	/**
	 * @var User
	 */
	protected $performer = null;

	/**
	 * @param UserManager $userManager
	 * @param User $user
	 * @param Status &$status
	 * @param User $performer
	 * @return bool
	 */
	public static function callback( $userManager, $user, &$status, $performer ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$userManager,
			$user,
			$status,
			$performer
		);
		return $hookHandler->process();
	}

	/**
	 * @param IContextSource $context
	 * @param Config $config
	 * @param UserManager $userManager
	 * @param User $user
	 * @param Status &$status
	 * @param User $performer
	 */
	public function __construct( $context, $config, $userManager, $user,
		&$status, $performer ) {
		parent::__construct( $context, $config );

		$this->userManager = $userManager;
		$this->user = $user;
		$this->status = &$status;
		$this->performer = $performer;
	}

}
