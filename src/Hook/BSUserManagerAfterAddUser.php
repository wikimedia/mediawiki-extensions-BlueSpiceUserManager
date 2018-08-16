<?php
/**
 * Hook handler base class for BlueSpice hook BSUserManagerAfterAddUser in
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
 * For further information visit http://bluespice.com
 *
 * @author     Patric Wirth <wirth@hallowelt.com>
 * @package    BlueSpiceFoundation
 * @copyright  Copyright (C) 2017 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 * @filesource
 */
namespace BlueSpice\UserManager\Hook;
use BlueSpice\Hook;

/**
 * Located in \BlueSpice\UserManager\Extension::addUser after a user was initially added
 */
abstract class BSUserManagerAfterAddUser extends Hook {

	/**
	 *
	 * @var \UserManager
	 */
	protected $userManager = null;

	/**
	 *
	 * @var \User
	 */
	protected $user = null;

	/**
	 *
	 * @var array
	 */
	protected $metaData = null;

	/**
	 *
	 * @var \Status
	 */
	protected $status = null;

	/**
	 *
	 * @var \User
	 */
	protected $performer = null;

	/**
	 * @param \UserManager $userManager
	 * @param \User $user
	 * @param array $metaData
	 * @param \Status $status
	 * @param \User $performer
	 * @return boolean
	 */
	public static function callback( $userManager, $user, $metaData, &$status, $performer ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$userManager,
			$user,
			$metaData,
			$status,
			$performer
		);
		return $hookHandler->process();
	}

	/**
	 * @param \IContextSource $context
	 * @param \Config $config
	 * @param \UserManager $userManager
	 * @param \User $user
	 * @param array $metaData
	 * @param \Status $status
	 * @param \User $performer
	 */
	public function __construct( $context, $config, $userManager, $user, $metaData, &$status, $performer ) {
		parent::__construct( $context, $config );

		$this->userManager = $userManager;
		$this->user = $user;
		$this->metaData = $metaData;
		$this->status = &$status;
		$this->performer = $performer;
	}
}