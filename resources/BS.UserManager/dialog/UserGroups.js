/**
 * UserManager UserGroups Dialog
 *
 * Part of BlueSpice MediaWiki
 *
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @package    Bluespice_Extensions
 * @subpackage UserManager
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 * @filesource
 */

Ext.define( 'BS.UserManager.dialog.UserGroups', {
	extend: 'MWExt.Dialog',
	requires: ['BS.UserManager.form.field.GroupList'],
	currentData: {},
	selectedData: {},
	maxHeight: 620,
	title: mw.message('bs-usermanager-headergroups').plain(),
	makeItems: function() {

		this.cbGroups = new BS.UserManager.form.field.GroupList();
		return [
			this.cbGroups
		];
	},
	setData: function( obj ) {
		this.currentData = obj;
		this.cbGroups.setValue( obj.groups );
	},
	getData: function() {
		this.selectedData.groups = this.cbGroups.getValue();
		return this.selectedData;
	}
} );