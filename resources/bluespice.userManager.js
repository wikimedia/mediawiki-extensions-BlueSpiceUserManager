/**
 * UserManager extension
 *
 * @author     Stephan Muggli <muggli@hallowelt.com>
 * @package    Bluespice_Extensions
 * @subpackage UserManager
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */

(function( mw, $, bs, d, undefined){
	Ext.onReady( function(){
		Ext.Loader.setPath(
			'BS.UserManager',
			bs.em.paths.get( 'BlueSpiceUserManager' ) + '/resources/BS.UserManager'
		);

		var bsgTaskAPIPermissions = mw.config.get( 'bsgTaskAPIPermissions' );
		Ext.create( 'BS.UserManager.panel.Manager', {
			renderTo: 'bs-usermanager-grid',
			operationPermissions: {
				'create': bsgTaskAPIPermissions.usermanager.addUser,
				'delete': bsgTaskAPIPermissions.usermanager.deleteUser,
				'disableuser': bsgTaskAPIPermissions.usermanager.disableUser,
				'usergroups': bsgTaskAPIPermissions.usermanager.setUserGroups,
				'editpassword': bsgTaskAPIPermissions.usermanager.editPassword,
				'update': bsgTaskAPIPermissions.usermanager.editUser,
				'enableuser': bsgTaskAPIPermissions.usermanager.enableUser
			}
		} );
	} );

})(mediaWiki, jQuery, blueSpice, document );
