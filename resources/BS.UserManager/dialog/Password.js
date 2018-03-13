
Ext.define( 'BS.UserManager.dialog.Password', {
	extend: 'MWExt.Dialog',
	currentData: {},
	selectedData: {},
	maxHeight: 620,
	title: mw.message( 'bs-usermanager-editpassword' ).plain(),

	makeItems: function() {
		this.tfUserID = Ext.create( 'Ext.form.field.Hidden', {
			name: 'userid'
		});
		this.tfPassword = Ext.create( 'Ext.form.TextField', {
			inputType: 'password',
			fieldLabel: mw.message( 'bs-usermanager-labelnewpassword' ).plain(),
			labelWidth: 130,
			labelAlign: 'right',
			name: 'pass',
			allowBlank: false
		});
		this.tfRePassword = Ext.create( 'Ext.form.TextField', {
			inputType: 'password',
			fieldLabel: mw.message( 'bs-usermanager-labelpasswordcheck' ).plain(),
			labelWidth: 130,
			labelAlign: 'right',
			name: 'repass',
			allowBlank: false
		});

		return [
			this.tfUserID,
			this.tfPassword,
			this.tfRePassword
		];
	},

	setData: function( obj ) {
		this.currentData = obj;
		this.tfUserID.setValue( this.currentData.user_id );
	},

	getData: function() {
		this.selectedData.user_id = this.tfUserID.getValue();
		this.selectedData.user_password = this.tfPassword.getValue();
		this.selectedData.user_repassword = this.tfRePassword.getValue();

		return this.selectedData;
	},

	resetData: function() {
		this.tfUserID.reset();
		this.tfPassword.reset();
		this.tfRePassword.reset();
	}
});
