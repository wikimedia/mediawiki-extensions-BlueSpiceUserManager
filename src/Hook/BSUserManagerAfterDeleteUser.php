<?php

/**
 * Hook handler base class for BlueSpice hook BSUserManagerAfterDeleteUser in
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
 * @author     Patric Wirth
 * @package    BlueSpiceFoundation
 * @copyright  Copyright (C) 2020 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */

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
	 *
	 * @var UserManager
	 */
	protected $userManager = null;

	/**
	 *
	 * @var User
	 */
	protected $user = null;

	/**
	 *
	 * @var Status
	 */
	protected $status = null;

	/**
	 *
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
