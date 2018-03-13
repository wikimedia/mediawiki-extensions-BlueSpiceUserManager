/**
 * UserManager UserDialog
 *
 * Part of BlueSpice MediaWiki
 *
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @author     Stephan Muggli <muggli@hallowelt.com>
 * @package    Bluespice_Extensions
 * @subpackage UserManager
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 * @filesource
 */

Ext.define( 'BS.UserManager.dialog.AddUser', {
	extend: 'MWExt.Dialog',
	requires: [ 'BS.UserManager.form.field.GroupList' ],
	currentData: {},
	selectedData: {},
	maxHeight: 620,
	makeItems: function() {
		this.tfUserName = Ext.create( 'Ext.form.TextField', {
			fieldLabel: mw.message('bs-usermanager-headerusername').plain(),
			labelWidth: 130,
			labelAlign: 'right',
			name: 'username',
			allowBlank: false
		});
		this.tfPassword = Ext.create( 'Ext.form.TextField', {
			inputType: 'password',
			fieldLabel: mw.message('bs-usermanager-labelnewpassword').plain(),
			labelWidth: 130,
			labelAlign: 'right',
			name: 'pass',
			allowBlank: false
		});
		this.tfRePassword = Ext.create( 'Ext.form.TextField', {
			inputType: 'password',
			fieldLabel: mw.message('bs-usermanager-labelpasswordcheck').plain(),
			labelWidth: 130,
			labelAlign: 'right',
			name: 'repass',
			allowBlank: false
		});
		this.tfEmail = Ext.create( 'Ext.form.TextField', {
			fieldLabel: mw.message('bs-usermanager-headeremail').plain(),
			labelWidth: 130,
			labelAlign: 'right',
			name: 'email'
		});
		this.tfRealName = Ext.create( 'Ext.form.TextField', {
			fieldLabel: mw.message('bs-usermanager-headerrealname').plain(),
			labelWidth: 130,
			labelAlign: 'right',
			name: 'realname'
		});
		this.tfEnabled = Ext.create( 'Ext.form.Checkbox', {
			fieldLabel: mw.message('bs-usermanager-headerenabled').plain(),
			labelWidth: 130,
			labelAlign: 'right',
			name: 'enabled',
			checked: true
		});
		this.cbGroups = new BS.UserManager.form.field.GroupList();

		return [
			this.tfUserName,
			this.tfPassword,
			this.tfRePassword,
			this.tfEmail,
			this.tfRealName,
			this.tfEnabled,
			this.cbGroups
		];
	},
	resetData: function() {
		this.tfUserName.reset();
		this.tfPassword.reset();
		this.tfRePassword.reset();
		this.tfEmail.reset();
		this.tfRealName.reset();
		this.tfEnabled.reset();
		this.cbGroups.reset();

		this.callParent(arguments);
	},
	setData: function( obj ) {
		this.currentData = obj;

		this.tfUserName.setValue( this.currentData.user_name );
		this.tfEmail.setValue( this.currentData.user_email );
		this.tfRealName.setValue( this.currentData.user_real_name );
		this.tfEnabled.setValue(this.currentData.enabled );
		this.cbGroups.setValue( this.currentData.groups );
	},
	getData: function() {
		this.selectedData.user_name = this.tfUserName.getValue();
		this.selectedData.user_password = this.tfPassword.getValue();
		this.selectedData.user_repassword = this.tfRePassword.getValue();
		this.selectedData.user_email = this.tfEmail.getValue();
		this.selectedData.user_real_name = this.tfRealName.getValue();
		this.selectedData.enabled = this.tfEnabled.getValue();
		this.selectedData.groups = this.cbGroups.getValue();

		return this.selectedData;
	}
} );
