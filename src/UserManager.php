<?php

namespace BlueSpice\UserManager;

use InvalidArgumentException;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Auth\TemporaryPasswordAuthenticationRequest;
use MediaWiki\Auth\UserDataAuthenticationRequest;
use MediaWiki\Auth\UsernameAuthenticationRequest;
use MediaWiki\Block\AbstractBlock;
use MediaWiki\Block\BlockManager;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Block\DatabaseBlockStore;
use MediaWiki\Deferred\SiteStatsUpdate;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Message\Message;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Permissions\Authority;
use MediaWiki\Status\Status;
use MediaWiki\User\PasswordReset;
use MediaWiki\User\User;
use MediaWiki\User\UserGroupManager;
use MediaWiki\Utils\MWTimestamp;
use PermissionsError;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use StatusValue;

class UserManager implements LoggerAwareInterface {

	/**
	 * @var LoggerInterface|null
	 */
	private ?LoggerInterface $logger = null;

	/**
	 * @var string
	 */
	private string $domain;

	/**
	 * @var BlockManager
	 */
	private BlockManager $blockManager;

	/**
	 * @var DatabaseBlockStore
	 */
	private DatabaseBlockStore $databaseBlockStore;

	/**
	 * @var HookContainer
	 */
	private HookContainer $hookContainer;

	/**
	 * @var AuthManager
	 */
	private AuthManager $authManager;

	/**
	 * @var UserGroupManager
	 */
	private UserGroupManager $userGroupManager;

	/**
	 * @var PasswordReset
	 */
	private PasswordReset $passwordReset;

	/**
	 * @param BlockManager $blockManager
	 * @param DatabaseBlockStore $databaseBlockStore
	 * @param HookContainer $hookContainer
	 * @param AuthManager $authManager
	 * @param UserGroupManager $userGroupManager
	 * @param PasswordReset $passwordReset
	 */
	public function __construct(
		BlockManager $blockManager, DatabaseBlockStore $databaseBlockStore, HookContainer $hookContainer,
		AuthManager $authManager, UserGroupManager $userGroupManager, PasswordReset $passwordReset
	) {
		$this->blockManager = $blockManager;
		$this->databaseBlockStore = $databaseBlockStore;
		$this->hookContainer = $hookContainer;
		$this->authManager = $authManager;
		$this->userGroupManager = $userGroupManager;
		$this->passwordReset = $passwordReset;
	}

	/**
	 * @param User $user
	 * @param array $params
	 * @param Authority $actor
	 * @return void
	 * @throws PermissionsError
	 */
	public function addUser( User $user, array $params, Authority $actor ) {
		$this->overrideDomain();

		if ( $user->isRegistered() ) {
			$this->throw( InvalidArgumentException::class, 'bs-usermanager-user-exists' );
		}
		$this->assertActorCan( 'add', $user, $actor );
		$data = $this->getValidatedData( $params, [ 'password' ] );

		if ( !$user->isValidPassword( $data['password'] ) ) {
			$this->throw( InvalidArgumentException::class, 'bs-usermanager-invalid-pwd' );
		}

		$usernameReq = new UsernameAuthenticationRequest();
		$usernameReq->username = $user->getName();

		$userDataReq = new UserDataAuthenticationRequest();
		$userDataReq->email = $data['email'];
		$userDataReq->realname = $data['realName'];
		$userDataReq->username = $user->getName();

		$tempPassReq = new TemporaryPasswordAuthenticationRequest();
		$tempPassReq->username = $user->getName();
		$tempPassReq->password = $data['password'];
		$tempPassReq->mailpassword = !empty( $data['email'] );

		$authResponse = $this->authManager->beginAccountCreation( $actor, [
			$usernameReq,
			$userDataReq,
			$tempPassReq
		], '' );

		if ( $authResponse->status !== $authResponse::PASS ) {
			$this->throw( RuntimeException::class, $authResponse->message->text() );
		}

		// Reload user
		$user->load();
		if ( $user->getEmail() ) {
			// Auto-verify mail address, since user already used it for first login
			$user->setEmailAuthenticationTimestamp( MWTimestamp::now() );
			$user->saveSettings();
		}

		if ( !$data['enabled'] ) {
			$this->blockUser( $user, $actor );
		}
		$this->restoreDomain();

		$this->hookContainer->run( 'LocalUserCreated', [ $user, false ] );
		$status = Status::newGood();
		$this->hookContainer->run( 'BSUserManagerAfterAddUser', [ $this, $user, $data, &$status, $actor ] );
		if ( !$status->isOK() ) {
			$this->throwFromStatus( $status );
		}

		$siteStatsUpdate = SiteStatsUpdate::factory( [ 'users' => 1 ] );
		$siteStatsUpdate->doUpdate();

		return $status;
	}

	/**
	 * @param User $user
	 * @param array $data
	 * @param Authority $actor
	 * @return void
	 * @throws PermissionsError
	 */
	public function updateUser( User $user, array $data, Authority $actor ) {
		if ( !$user->isRegistered() ) {
			$this->throw( InvalidArgumentException::class, 'bs-usermanager-idnotexist' );
		}
		$this->assertActorCan( 'edit', $user, $actor );
		$data = $this->getValidatedData( $data );
		if ( isset( $data['realName'] ) ) {
			$user->setRealName( $data['realName'] );
		}
		if ( isset( $data['email'] ) ) {
			$user->setEmail( $data['email'] );
			$user->setEmailAuthenticationTimestamp( MWTimestamp::now() );
		}
		$user->saveSettings();
		$this->logger->info( 'User details updated', [
			'user' => $user->getName(),
			'data' => $data,
			'actor' => $actor->getUser()->getName()
		] );
		$block = $this->getBlock( $user );
		if ( $data['enabled'] && $block instanceof DatabaseBlock ) {
			$this->unblockUser( $block, $user, $actor );
		} elseif ( !$data['enabled'] && $block === null ) {
			$this->blockUser( $user, $actor );
		}

		$status = Status::newGood();
		$this->hookContainer->run(
			'BSUserManagerAfterEditUser',
			[ $this, $user, $data, &$status, $actor->getUser() ]
		);
		if ( !$status->isOK() ) {
			$this->throwFromStatus( $status );
		}
	}

	/**
	 * @param User $user
	 * @param array $groups
	 * @param Authority $actor
	 * @return void
	 * @throws PermissionsError
	 */
	public function setGroups( User $user, array $groups, Authority $actor ) {
		$this->assertActorCan( 'setGroups', $user, $actor );
		$attemptChangeSelf = $actor->getUser()->getId() == $user->getId();
		$excludeGroups = [ '*', 'user', 'autoconfirmed', 'emailconfirmed' ];

		$checkSelfSysopRemove = $attemptChangeSelf
			&& in_array( 'sysop', $this->userGroupManager->getUserEffectiveGroups( $actor->getUser() ) )
			&& !in_array( 'sysop', $groups );
		if ( $checkSelfSysopRemove ) {
			$this->throw( RuntimeException::class, 'bs-usermanager-no-self-desysop' );
		}

		$oldUGMs = $this->userGroupManager->getUserGroupMemberships( $user );
		$currentGroups = $this->userGroupManager->getUserGroups( $user );
		$addGroups = array_diff( $groups, $currentGroups );
		$removeGroups = array_diff( $currentGroups, $groups );
		$reallyAdd = [];
		$reallyRemove = [];

		$changeableGroups = $this->userGroupManager->getGroupsChangeableBy( $actor );

		foreach ( $addGroups as $group ) {
			if ( in_array( $group, $excludeGroups ) ) {
				continue;
			}
			if (
				!in_array( $group, $changeableGroups['add'] ) &&
				( !$attemptChangeSelf || !in_array( $group, $changeableGroups['add-self'] ) )
			) {
				$this->throw( RuntimeException::class, 'bs-usermanager-group-add-not-allowed', [ $group ] );
			}
			$reallyAdd[] = $group;
			$this->userGroupManager->addUserToGroup( $user, $group );
		}
		foreach ( $removeGroups as $group ) {
			if ( in_array( $group, $excludeGroups ) ) {
				continue;
			}

			if (
				!in_array( $group, $changeableGroups['remove'] ) &&
				( !$attemptChangeSelf || !in_array( $group, $changeableGroups['remove-self'] ) )
			) {
				$this->throw( RuntimeException::class, 'bs-usermanager-group-remove-not-allowed', [ $group ] );
			}
			$reallyRemove[] = $group;
			$this->userGroupManager->removeUserFromGroup( $user, $group );
		}

		$status = Status::newGood( $user );
		$this->hookContainer->run( 'UserGroupsChanged', [
			$user,
			$reallyAdd,
			$reallyRemove,
			$actor->getUser(),
			'',
			$oldUGMs,
			$this->userGroupManager->getUserGroupMemberships( $user )
		] );
		$this->hookContainer->run(
			'BSUserManagerAfterSetGroups',
			[
				$user,
				$groups,
				$addGroups,
				$removeGroups,
				$excludeGroups,
				&$status
			]
		);

		if ( !$status->isOK() ) {
			$this->throwFromStatus( $status );
		}

		$user->invalidateCache();
		return $status;
	}

	public function resetPassword( User $user, array $params, Authority $actor ) {
		$this->assertActorCan( 'editPassword', $user, $actor );
		$strategy = $params['strategy'] ?? '';
		if ( $strategy === 'reset' ) {
			if ( !$user->canReceiveEmail() ) {
				$this->throw( InvalidArgumentException::class, 'bs-usermanager-no-mail' );
			}
			$resetStatus = $this->passwordReset->execute( $actor, $user->getName(), $user->getEmail() );
			if ( !$resetStatus->isOK() ) {
				$this->throwFromStatus( $resetStatus );
			}
			return;
		}

		$password = $params['password'] ?? '';
		if ( empty( $params['password'] ) ) {
			$this->throw( InvalidArgumentException::class, 'bs-usermanager-invalid-pwd' );
		}
		if ( empty( $params['repassword'] ) ) {
			$this->throw( InvalidArgumentException::class, 'bs-usermanager-invalid-pwd' );
		}

		if ( !$user->isValidPassword( $password ) ) {
			$this->throw( InvalidArgumentException::class, 'bs-usermanager-invalid-pwd' );
		}
		if ( mb_strtolower( $user->getName() ) === mb_strtolower( $password ) ) {
			$this->throw( InvalidArgumentException::class, 'password-name-match' );
		}
		$rePassword = $params['repassword'];
		if ( empty( $rePassword ) || $password !== $rePassword ) {
			$this->throw( InvalidArgumentException::class, 'badretype' );
		}

		$changeStatus = $user->changeAuthenticationData( [
			'password' => $password,
			'retype' => $password ]
		);

		if ( !$changeStatus->isOK() ) {
			$this->throwFromStatus( $changeStatus );
		}

		$user->saveSettings();
	}

	/**
	 * @return void
	 */
	private function overrideDomain() {
		// This is to overcome username case issues with custom AuthPlugin (i.e. LDAPAuth)
		// LDAPAuth would otherwise turn the username to first-char-upper-rest-lower-case
		// At the end of this method we switch $_SESSION['wsDomain'] back again
		$this->domain = isset( $_SESSION['wsDomain'] ) ? $_SESSION['wsDomain'] : '';
		$_SESSION['wsDomain'] = 'local';
	}

	/**
	 * @return void
	 */
	private function restoreDomain() {
		$_SESSION['wsDomain'] = $this->domain;
	}

	/**
	 * @param LoggerInterface|null $logger
	 */
	public function setLogger( ?LoggerInterface $logger ): void {
		$this->logger = $logger;
	}

	/**
	 * @param string $action
	 * @param User $user
	 * @param Authority $actor
	 * @return void
	 * @throws PermissionsError
	 */
	private function assertActorCan( string $action, User $user, Authority $actor ) {
		$permissions = [
			'add' => [ 'wikiadmin' ],
			'edit' => [ 'wikiadmin' ],
			'setGroups' => [ 'userrights' ],
			'editPassword' => [ 'userrights', 'usermanager-editpassword' ],
		];
		$permission = $permissions[$action] ?? null;
		if ( !$permission ) {
			throw new InvalidArgumentException( "Unknown action: $action" );
		}

		if ( !$actor->isAllowedAll( ...$permission ) ) {
			throw new PermissionsError( 'wikiadmin' );
		}
	}

	/**
	 * @param array $data
	 * @param array $mustExist
	 * @return array
	 */
	private function getValidatedData( array $data, array $mustExist = [] ) {
		foreach ( $mustExist as $key ) {
			if ( !isset( $data[$key] ) ) {
				$this->throw( InvalidArgumentException::class, 'bs-usermanager-missing-param', [ $key ] );
			}
		}
		if ( isset( $data['email'] ) ) {
			if ( !empty( $data['email'] ) && !Sanitizer::validateEmail( $data['email'] ) ) {
				$this->throw( InvalidArgumentException::class, 'bs-usermanager-invalid-email-gen' );
			}
		}

		if ( !isset( $data['enabled'] ) ) {
			$data['enabled'] = true;
		}

		return $data;
	}

	/**
	 * @param User $user
	 * @return AbstractBlock|null
	 */
	public function getBlock( User $user ): ?AbstractBlock {
		return $this->blockManager->getBlock( $user, null );
	}

	/**
	 * @param DatabaseBlock $block
	 * @param User $user
	 * @param Authority $actor
	 * @return void
	 */
	public function unblockUser( DatabaseBlock $block, User $user, Authority $actor ) {
		$reason = '';
		if ( !$this->hookContainer->run( 'UnblockUser', [ $block, $actor->getUser(), &$reason ] ) ) {
			$this->logger->warning( 'Unblocking user failed due to UnblockUser hook', [
				'user' => $user->getName(),
				'actor' => $actor->getUser()->getName()
			] );
			return;
		}
		if ( !$this->databaseBlockStore->deleteBlock( $block ) ) {
			$this->logger->error( 'Failed to unblock user', [
				'user' => $user->getName(),
				'actor' => $actor->getUser()->getName()
			] );
			$this->throw( RuntimeException::class, 'bs-usermanager-unblock-error', [ $user->getName() ] );
		}

		$this->logger->info( 'User unblocked', [
			'user' => $user->getName(),
			'actor' => $actor->getUser()->getName()
		] );
	}

	/**
	 * @param User $user
	 * @param Authority $actor
	 * @return void
	 */
	public function blockUser( User $user, Authority $actor ) {
		if ( $user->getId() == $actor->getUser()->getId() ) {
			$this->throw( RuntimeException::class, 'bs-usermanager-no-self-block' );
		}

		# Create block object.
		$block = new DatabaseBlock();
		$block->setBlocker( $actor->getUser() );
		$block->setTarget( $user );
		$block->setExpiry( 'infinity' );
		$block->setReason( Message::newFromKey( 'bs-usermanager-log-user-disabled', $user->getName() )->text() );
		$block->isEmailBlocked( true );
		$block->isCreateAccountBlocked( false );
		$block->isUsertalkEditAllowed( true );
		$block->isHardblock( true );
		$block->isAutoblocking( false );

		$reason = [ 'hookaborted' ];
		$res = $this->hookContainer->run( 'BlockIp', [
			&$block,
			&$actor,
			&$reason
		] );
		if ( !$res ) {
			$this->logger->error( 'Blocking user failed due to BlockIp hook', [
				'user' => $user->getName(),
				'actor' => $actor->getUser()->getName(),
				'reason' => $reason
			] );
			$this->throw( RuntimeException::class, $reason );
		}

		$blockStatus = $this->databaseBlockStore->insertBlock( $block );
		if ( !$blockStatus ) {
			$this->logger->error( 'Failed to block user', [
				'user' => $user->getName(),
				'actor' => $actor->getUser()->getName()
			] );
			$this->throw( RuntimeException::class, 'bs-usermanager-block-error', [ $user->getName() ] );
		}

		$this->logger->info( 'User blocked', [
			'user' => $user->getName(),
			'actor' => $actor->getUser()->getName()
		] );
	}

	/**
	 * @param string $exceptionClass
	 * @param string|array $messageKey
	 * @param array $args
	 */
	private function throw( string $exceptionClass, $messageKey, array $args = [] ) {
		$msg = [];
		if ( is_string( $messageKey ) ) {
			$messageKey = [ $messageKey ];
		}
		foreach ( $messageKey as $key ) {
			$msg[] = Message::newFromKey( $key, ...$args )->text();
		}

		throw new $exceptionClass( implode( ", ", $msg ) );
	}

	/**
	 * @param StatusValue $status
	 */
	private function throwFromStatus( StatusValue $status ) {
		$messages = [];
		foreach ( $status->getMessages() as $specifier ) {
			$messages[] = Message::newFromSpecifier( $specifier );
		}
		$this->logger->error( 'Hook failure', [
			'messages' => $messages
		] );
		throw new RuntimeException( implode( ", ", $messages ) );
	}

}
