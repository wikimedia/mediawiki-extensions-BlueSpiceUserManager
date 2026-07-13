<?php

namespace BlueSpice\UserManager\Hook;

use BlueSpice\Hook;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\Status\Status;
use MediaWiki\User\User;

abstract class BSUserManagerAfterSetGroups extends Hook {

	/**
	 * @var User
	 */
	protected $user;

	/**
	 * @var array
	 */
	protected $groups;

	/**
	 * @var array
	 */
	protected $addGroups;

	/**
	 * @var array
	 */
	protected $removeGroups;

	/**
	 * @var array
	 */
	protected $excludeGroups;

	/**
	 * @var Status
	 */
	protected $status;

	/**
	 * @param User $user
	 * @param array $groups
	 * @param array $addGroups
	 * @param array $removeGroups
	 * @param array $excludeGroups
	 * @param Status &$status
	 * @return bool
	 */
	public static function callback( $user, $groups, $addGroups, $removeGroups,
		$excludeGroups, &$status ) {
		$className = static::class;
		$hookHandler = new $className(
			null, null, $user, $groups, $addGroups, $removeGroups, $excludeGroups,
			$status
		);
		return $hookHandler->process();
	}

	/**
	 * @param IContextSource $context
	 * @param Config $config
	 * @param User $user
	 * @param array $groups
	 * @param array $addGroups
	 * @param array $removeGroups
	 * @param array $excludeGroups
	 * @param Status &$status
	 */
	public function __construct( $context, $config, $user, $groups, $addGroups,
		$removeGroups, $excludeGroups, &$status ) {
		parent::__construct( $context, $config );

		$this->user = $user;
		$this->groups = $groups;
		$this->addGroups = $addGroups;
		$this->removeGroups = $removeGroups;
		$this->excludeGroups = $excludeGroups;
		$this->status =& $status;
	}

}
