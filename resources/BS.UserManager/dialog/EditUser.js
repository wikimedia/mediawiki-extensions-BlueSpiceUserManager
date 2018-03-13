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

Ext.define( 'BS.UserManager.dialog.EditUser', {
	extend: 'MWExt.Dialog',
	currentData: {},
	selectedData: {},
	maxHeight: 620,
	makeItems: function() {
		this.tfUserID = Ext.create( 'Ext.form.field.Hidden', {
			name: 'userid'
		});
		this.tfUserName = Ext.create( 'Ext.form.TextField', {
			fieldLabel: mw.message('bs-usermanager-headerusername').plain(),
			labelWidth: 130,
			labelAlign: 'right',
			name: 'username',
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

		return [
			this.tfUserName,
			this.tfEmail,
			this.tfRealName,
			this.tfEnabled
		];
	},
	resetData: function() {
		this.tfUserName.reset();
		this.tfEmail.reset();
		this.tfRealName.reset();
		this.tfEnabled.reset();

		this.callParent(arguments);
	},
	setData: function( obj ) {
		this.currentData = obj;
		this.tfUserID.setValue(this.currentData.user_id);
		this.tfUserName.setValue( this.currentData.user_name );
		this.tfEmail.setValue( this.currentData.user_email );
		this.tfRealName.setValue( this.currentData.user_real_name );
		this.tfEnabled.setValue(this.currentData.enabled )
	},
	getData: function() {
		this.selectedData.user_id = this.tfUserID.getValue();
		this.selectedData.user_name = this.tfUserName.getValue();
		this.selectedData.user_email = this.tfEmail.getValue();
		this.selectedData.user_real_name = this.tfRealName.getValue();
		this.selectedData.enabled = this.tfEnabled.getValue();

		return this.selectedData;
	},

} );
