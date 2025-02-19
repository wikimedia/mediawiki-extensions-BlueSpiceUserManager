<?php

/**
 * Hook handler base class for BlueSpice hook BSUserManagerAfterSetGroups in
 * UserManager
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit https://bluespice.com
 *
 * @author     Dejan Savuljesku <savuljesku@hallowelt.com>
 * @package    BlueSpiceUserManager
 * @copyright  Copyright (C) 2017 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */

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
	 *
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
	 *
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
