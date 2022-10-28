<?php

use BlueSpice\UserManager\Extension as UserManager;
use MediaWiki\MediaWikiServices;

/**
 * Maintenance script to delete all blocked user from databse
 *
 * @file
 * @ingroup Maintenance
 * @author Marc Reymann
 * @license GPL-3.0-only
 */

require_once '../../BlueSpiceFoundation/maintenance/BSMaintenance.php';

class RemoveBlockedUser extends BSMaintenance {

	/**
	 *
	 */
	public function __construct() {
		parent::__construct();

		$this->addOption( 'user', 'Username of performing user.', true, true, 'u' );
		$this->addOption( 'force', 'Remove blocked user from database.' );
	}

	/**
	 *
	 */
	public function execute() {
		$performerName = $this->getOption( 'user' );
		$performer = MediaWikiServices::getInstance()->getUserFactory()
			->newFromName( $performerName );

		if ( $performer->getId() !== 0 ) {
			$force = false;
			if ( $this->hasOption( 'force' ) ) {
				$force = true;

				$this->output( "This run and will remove blocked user from database.\n\n" );
			} else {
				$this->output( "This is a dry run and will NOT affact blocked user in database.\n\n" );
			}

			$allUser = $this->getAllUserFromDB();

			$userFactory = MediaWikiServices::getInstance()->getUserFactory();
			foreach ( $allUser as $user ) {
				$id = $user['id'];
				$name = $user['name'];

				$currentUser = $userFactory->newFromId( $id );
				if ( !$currentUser->isBlocked() ) {
					continue;
				}

				$this->output( "( $id )  $name" );

				if ( $force === true ) {
					$status = $this->deleteUserFromDB( $currentUser, $performer );
					$this->output( " ... $status" );
				}

				$this->output( "\n" );
			}
		} else {
			$this->output( "Performing user does not exist.\n\n" );
		}
	}

	/**
	 *
	 * @return array
	 */
	private function getAllUserFromDB() {
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			'user',
			[
				'user_id',
				'user_name'
			]
		);

		if ( !$res ) {
			return [];
		}

		$allUser = [];
		foreach ( $res as $row ) {
			$allUser[] = [
				'id' => $row->user_id,
				'name'  => $row->user_name
			];
		}

		return $allUser;
	}

	/**
	 *
	 * @param User $user
	 * @param User $performer
	 * @return string
	 */
	private function deleteUserFromDB( $user, $performer ) {
		$status = UserManager::deleteUser( $user, $performer );

		if ( $status->isGood() ) {
			return 'deleted';
		} else {
			return 'fail';
		}
	}
}

$maintClass = 'RemoveBlockedUser';
require_once RUN_MAINTENANCE_IF_MAIN;
