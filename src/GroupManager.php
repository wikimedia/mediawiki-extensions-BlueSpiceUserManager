<?php

namespace BlueSpice\UserManager;

use BlueSpice\UserManager\Logging\GroupManagerSpecialLogLogger;
use InvalidArgumentException;
use MediaWiki\Config\Config;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\Authority;
use MediaWiki\User\User;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\ILoadBalancer;

class GroupManager {

	/** @var DynamicConfigManager */
	private DynamicConfigManager $configManager;
	/** @var ILoadBalancer */
	private ILoadBalancer $lb;
	/** @var Config */
	private Config $config;
	/** @var HookContainer */
	private HookContainer $hookContainer;
	/** @var LoggerInterface */
	private LoggerInterface $logger;
	/** @var GroupManagerSpecialLogLogger */
	private GroupManagerSpecialLogLogger $spLogger;

	/**
	 * @param DynamicConfigManager $configManager
	 * @param ILoadBalancer $lb
	 * @param Config $config Main config (wg)
	 * @param HookContainer $hookContainer
	 * @param LoggerInterface $logger
	 * @param GroupManagerSpecialLogLogger $spLogger
	 */
	public function __construct(
		DynamicConfigManager $configManager, ILoadBalancer $lb, Config $config,
		HookContainer $hookContainer, LoggerInterface $logger, GroupManagerSpecialLogLogger $spLogger
	) {
		$this->configManager = $configManager;
		$this->lb = $lb;
		$this->config = $config;
		$this->hookContainer = $hookContainer;
		$this->logger = $logger;
		$this->spLogger = $spLogger;
	}

	/**
	 * @param string $name
	 * @param Authority $actor
	 * @param bool $ignoreExists
	 * @return string
	 */
	public function addGroup( string $name, Authority $actor, bool $ignoreExists = false ) {
		$this->assertActorCan( 'add', $actor );
		$this->hookContainer->run( 'BSUserManagerBeforeAddGroup', [ &$name, $actor ] );
		$this->assertValidName( $name, $ignoreExists );
		$current = $this->config->get( 'AdditionalGroups' ) ?? [];
		$current[$name] = true;
		$this->store( $current );
		$this->hookContainer->run( 'BSUserManagerGroupAdded', [ $name, $actor ] );
		$this->log( 'create', $actor, [ 'group' => $name ] );
		return $name;
	}

	/**
	 * @param string $oldName
	 * @param string $newName
	 * @param Authority $actor
	 * @return void
	 */
	public function editGroup( string $oldName, string $newName, Authority $actor ) {
		$this->assertActorCan( 'edit', $actor );
		$this->assertValidName( $newName );
		$this->assertGroupExists( $oldName );
		$current = $this->config->get( 'AdditionalGroups' ) ?? [];
		unset( $current[$oldName] );
		$current[$newName] = true;
		$this->renameGroup( $oldName, $newName );
		$this->store( $current );
		$this->hookContainer->run( 'BSUserManagerGroupEdited', [ $oldName, $newName, $actor ] );
		$this->log( 'modify', $actor, [ 'group' => $oldName, 'newGroup' => $newName ] );
	}

	/**
	 * @param string $name
	 * @param Authority $actor
	 * @return void
	 */
	public function removeGroup( string $name, Authority $actor ) {
		$this->assertActorCan( 'delete', $actor );
		$this->assertGroupExists( $name );
		$current = $this->config->get( 'AdditionalGroups' ) ?? [];
		unset( $current[$name] );
		$this->store( $current );
		$this->unassignUsers( $name );
		$this->hookContainer->run( 'BSUserManagerGroupDeleted', [ $name, $actor ] );
		$this->log( 'remove', $actor, [ 'group' => $name ] );
	}

	/**
	 * @param string $name
	 * @return array
	 */
	public function getGroupMembers( string $name ) {
		$this->assertGroupExists( $name );
		return $this->getMembers( $name );
	}

	/**
	 * @param string $name
	 * @return array
	 */
	private function getMembers( $name ) {
		$dbr = $this->lb->getConnection( DB_REPLICA );

		$group = [ $name ];
		$user = [];
		$res = $dbr->select(
			[ 'user_groups', 'user' ],
			[ 'ug_user', 'user_name' ],
			[
				'ug_group' => $group,
				'user_id = ug_user'
			],
			__METHOD__,
			[ 'DISTINCT' ]
		);
		if ( !$res ) {
			return $user;
		}

		foreach ( $res as $row ) {
			$user[] = $row->user_name;
		}
		return $user;
	}

	/**
	 * @param string $name
	 * @param bool $ignoreExists
	 * @return void
	 *
	 * @throws InvalidArgumentException
	 */
	private function assertValidName( string $name, bool $ignoreExists = false ) {
		$invalidChars = [];
		$name = trim( $name );
		if ( substr_count( $name, '\'' ) > 0 ) {
			$invalidChars[] = '\'';
		}
		if ( substr_count( $name, '"' ) > 0 ) {
			$invalidChars[] = '"';
		}
		if ( !empty( $invalidChars ) ) {
			throw new InvalidArgumentException(
				Message::newFromKey( 'bs-usermanager-group-invalid-name' )
					->numParams( count( $invalidChars ) )
					->params( implode( ',', $invalidChars ) )
					->text()
			);
		} elseif ( preg_match( "/^[0-9]+$/", $name ) ) {
			throw new InvalidArgumentException(
				Message::newFromKey( 'bs-usermanager-group-invalid-name-numeric' )->text()
			);
		} elseif ( strlen( $name ) > 255 ) {
			throw new InvalidArgumentException(
				Message::newFromKey( 'bs-usermanager-group-invalid-name-length' )->text()
			);
		}
		if ( !$ignoreExists && $this->checkGroupExists( $name ) ) {
			throw new InvalidArgumentException(
				Message::newFromKey( 'bs-usermanager-group-already-exists' )->text()
			);
		}
	}

	/**
	 * @param array $value
	 * @return void
	 *
	 * @throws InvalidArgumentException
	 */
	private function store( array $value ) {
		$config = $this->configManager->getConfigObject( 'bs-groupmanager-groups' );
		if ( !$config ) {
			throw new InvalidArgumentException( 'Config object not found' );
		}
		$this->configManager->storeConfig( $config, $value );
	}

	/**
	 * @param string $oldName
	 * @param string $newName
	 * @return void
	 *
	 * @throws DBError
	 */
	private function renameGroup( string $oldName, string $newName ) {
		$db = $this->lb->getConnection( DB_PRIMARY );
		$res = $db->update(
			'user_groups',
			[ 'ug_group' => $newName ],
			[ 'ug_group' => $oldName ],
			__METHOD__
		);
		if ( !$res ) {
			throw new DBError( $db, $db->lastError() );
		}
	}

	/**
	 * @param string $name
	 * @return void
	 *
	 * @throws InvalidArgumentException
	 */
	public function assertGroupExists( string $name ) {
		if ( !$this->checkGroupExists( $name ) ) {
			throw new InvalidArgumentException(
				Message::newFromKey( 'bs-usermanager-group-not-existing' )->text()
			);
		}
	}

	/**
	 * @param string $action
	 * @param Authority $actor
	 * @return void
	 *
	 * @throws InvalidArgumentException
	 */
	private function assertActorCan( string $action, Authority $actor ) {
		if ( $actor instanceof User && $actor->isSystemUser() ) {
			return;
		}
		if ( !$actor->isAllowed( 'wikiadmin' ) ) {
			throw new InvalidArgumentException(
				Message::newFromKey( 'bs-usermanager-action-not-allowed' )->text()
			);
		}
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	private function checkGroupExists( string $name ): bool {
		$groupPermissions = $this->config->get( 'GroupPermissions' ) ?? [];
		$existingGroups = array_keys( $groupPermissions );
		return in_array( $name, $existingGroups );
	}

	/**
	 * @param string $type
	 * @param Authority $actor
	 * @param array $params
	 * @return void
	 */
	private function log( string $type, Authority $actor, array $params ) {
		// Special:Log logging
		$this->spLogger->log( $type, $actor, $params );
		// Structured logging
		$this->logger->info( 'New group created', array_merge( [
			'actor' => $actor->getUser()->getName(),
		], $params ) );
	}

	/**
	 * @param string $name
	 * @return void
	 */
	private function unassignUsers( string $name ) {
		$db = $this->lb->getConnection( DB_PRIMARY );
		$db->delete(
			'user_groups',
			[ 'ug_group' => $name ],
			__METHOD__
		);
	}

}
