
Ext.define( 'BS.UserManager.dialog.Password', {
	extend: 'MWExt.Dialog',
	currentData: {},
	selectedData: {},
	forceReset: false,
	hasEmail: false,
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
			hidden: true
		});
		this.tfRePassword = Ext.create( 'Ext.form.TextField', {
			inputType: 'password',
			fieldLabel: mw.message( 'bs-usermanager-labelpasswordcheck' ).plain(),
			labelWidth: 130,
			labelAlign: 'right',
			name: 'repass',
			hidden: true
		});

		this.chStrategy = Ext.create( 'Ext.form.RadioGroup', {
			name: 'force-reset',
			columns: 1,
			vertical: true,
			items: [
				{
					boxLabel: mw.message( 'bs-usermanager-label-password-change-strategy-reset' ).plain(),
					name: 'reset-strategy', inputValue: 'reset', disabled: !this.hasEmail
				},
				{
					boxLabel: mw.message( 'bs-usermanager-label-password-change-strategy-pw' ).plain(),
					name: 'reset-strategy', inputValue: 'password', disabled: this.forceReset && this.hasEmail
				}
			],
			fieldLabel: mw.message( 'bs-usermanager-label-password-change-strategy' ).plain(),
			labelWidth: 130,
			labelAlign: 'right'
		} );
		this.chStrategy.on( 'change', this.onStrategyChange.bind( this ) );

		this.chStrategy.setValue(  { "reset-strategy": this.hasEmail ? "reset" : "password" } );

		return [
			this.tfUserID,
			this.chStrategy,
			this.tfPassword,
			this.tfRePassword
		];
	},

	onStrategyChange: function( sender, val ) {
		val = val['reset-strategy'];
		this.tfPassword.setHidden( val === 'reset' );
		this.tfRePassword.setHidden( val === 'reset' );
	},

	setData: function( obj ) {
		this.currentData = obj;
		this.tfUserID.setValue( this.currentData.user_id );
	},

	getData: function() {
		this.selectedData.user_id = this.tfUserID.getValue();
		var strategy = this.chStrategy.getValue()['reset-strategy'];
		this.selectedData.strategy = strategy;
		if ( strategy === 'password' ) {
			this.selectedData.user_password = this.tfPassword.getValue();
			this.selectedData.user_repassword = this.tfRePassword.getValue();
		}

		return this.selectedData;
	},

	resetData: function() {
		this.tfUserID.reset();
		this.tfPassword.reset();
		this.tfRePassword.reset();
		this.chStrategy.reset();
	}
});
