bs.util.registerNamespace( 'bs.usermanager.ui' );

bs.usermanager.ui.AddUserPanel = function( cfg ) {
	cfg = cfg || {};
	cfg.isCreation = true;
	bs.usermanager.ui.AddUserPanel.parent.call( this, cfg );
};

OO.inheritClass( bs.usermanager.ui.AddUserPanel, bs.usermanager.ui.UserDetailsPanel );

bs.usermanager.ui.AddUserPanel.prototype.initialize = function() {
	this.passwordInput = new OO.ui.TextInputWidget( {
		type: 'password',
		required: true,
		classes: [ 'um-password' ]
	} );
	this.passwordRepeatInput = new OO.ui.TextInputWidget( {
		type: 'password',
		required: true,
		classes: [ 'um-password-repeat' ]
	} );
	this.passwordInput.connect( this, { change: 'validateOnChange' } );
	this.passwordRepeatInput.connect( this, { change: 'validateOnChange' } );
	bs.usermanager.ui.AddUserPanel.parent.prototype.initialize.call( this );
};

bs.usermanager.ui.AddUserPanel.prototype.makeForm = function() {
	const form = new OO.ui.FieldsetLayout( {
		classes: [ 'um-form' ]
	} );
	form.addItems( [
		new OO.ui.FieldLayout( this.usernameInput, {
			label: mw.msg( 'bs-usermanager-headerusername' ),
			align: 'left'
		} ),
		new OO.ui.FieldLayout( this.passwordInput, {
			label: mw.msg( 'bs-usermanager-labelnewpassword' ),
			align: 'left'
		} ),
		new OO.ui.FieldLayout( this.passwordRepeatInput, {
			label: mw.msg( 'bs-usermanager-labelpasswordcheck' ),
			align: 'left'
		} ),
		new OO.ui.FieldLayout( this.realNameInput, {
			label: mw.msg( 'bs-usermanager-headerrealname' ),
			align: 'left'
		} ),
		new OO.ui.FieldLayout( this.emailInput, {
			label: mw.msg( 'bs-usermanager-headeremail' ),
			align: 'left'
		} ),
		new OO.ui.FieldLayout( this.groupInput, {
			label: mw.msg( 'bs-usermanager-headergroups' ),
			align: 'left'
		} )
	] );

	this.$element.append( form.$element );
};

bs.usermanager.ui.AddUserPanel.prototype.getValidData = async function() {
	await this.checkValidity( [ this.usernameInput, this.realNameInput, this.emailInput ] );
	try {
		await this.passwordInput.getValidity();
		await this.passwordRepeatInput.getValidity();
		if ( this.passwordInput.getValue() !== this.passwordRepeatInput.getValue() ) {
			throw new OO.ui.Error( mw.msg( 'bs-usermanager-errorpasswordmismatch' ) );
		}
	} catch ( e ) {
		this.passwordInput.setValidityFlag( false );
		this.passwordRepeatInput.setValidityFlag( false );
		throw e;
	}
	return {
		username: this.usernameInput.getValue(),
		realName: this.realNameInput.getValue(),
		email: this.emailInput.getValue(),
		enabled: true,
		groups: this.groupInput.getValue(),
		password: this.passwordInput.getValue(),
		repassword: this.passwordRepeatInput.getValue()
	};
};
