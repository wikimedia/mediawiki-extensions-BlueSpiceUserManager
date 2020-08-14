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

		var bsTaskAPIPermissions = mw.config.get( 'bsTaskAPIPermissions' );
		Ext.create( 'BS.UserManager.panel.Manager', {
			renderTo: 'bs-usermanager-grid',
			operationPermissions: {
				'create': bsTaskAPIPermissions.usermanager.addUser,
				'delete': bsTaskAPIPermissions.usermanager.deleteUser,
				'disableuser': bsTaskAPIPermissions.usermanager.disableUser,
				'usergroups': bsTaskAPIPermissions.usermanager.setUserGroups,
				'editpassword': bsTaskAPIPermissions.usermanager.editPassword,
				'update': bsTaskAPIPermissions.usermanager.editUser,
				'enableuser': bsTaskAPIPermissions.usermanager.enableUser
			}
		} );
	} );

})(mediaWiki, jQuery, blueSpice, document );
