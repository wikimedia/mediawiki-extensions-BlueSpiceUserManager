bs.util.registerNamespace( 'bs.usermanager.ui.dialog' );

bs.usermanager.ui.dialog.AddUserDialog = function( cfg ) {
	cfg.isCreation = true;
	bs.usermanager.ui.dialog.AddUserDialog.parent.call( this, cfg );
};

OO.inheritClass( bs.usermanager.ui.dialog.AddUserDialog, bs.usermanager.ui.dialog.EditUserDialog );

bs.usermanager.ui.dialog.AddUserDialog.static.name = 'addUserDialog';
bs.usermanager.ui.dialog.AddUserDialog.static.title = mw.msg( 'bs-usermanager-titleadduser' );
bs.usermanager.ui.dialog.AddUserDialog.static.actions = [
	{ action: 'save', label: mw.msg( 'bs-usermanager-save' ), flags: [ 'primary', 'progressive' ] },
	{ action: 'cancel', label: mw.msg( 'bs-usermanager-cancel' ), flags: [ 'safe' ] }
];

bs.usermanager.ui.dialog.AddUserDialog.prototype.initialize = function() {
	bs.usermanager.ui.dialog.AddUserDialog.parent.prototype.initialize.call( this );
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
	this.content.$element.append(
		new OO.ui.FieldLayout( this.passwordInput, {
			label: mw.msg( 'bs-usermanager-labelnewpassword' ),
			align: 'left'
		} ).$element,
		new OO.ui.FieldLayout( this.passwordRepeatInput, {
			label: mw.msg( 'bs-usermanager-labelpasswordcheck' ),
			align: 'left'
		} ).$element
	);
};

bs.usermanager.ui.dialog.AddUserDialog.prototype.getValidData = async function() {
	var data = await bs.usermanager.ui.dialog.AddUserDialog.parent.prototype.getValidData.call( this );
	try {
		await this.passwordInput.getValidity();
		await this.passwordRepeatInput.getValidity();
	} catch ( e ) {
		this.passwordInput.setValidityFlag( false );
		this.passwordRepeatInput.setValidityFlag( false );
		throw e;
	}
	data.password = this.passwordInput.getValue();
	data.repassword = this.passwordRepeatInput.getValue();
	return data;
};

bs.usermanager.ui.dialog.AddUserDialog.prototype.saveData = async function( data ) {
	var dfd = $.Deferred();
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/bs-usermanager/v1/user/' + data.username,
		method: 'PUT',
		data: JSON.stringify( data ),
		dataType: 'json',
		contentType: 'application/json'
	} ).done( function() {
		dfd.resolve();
	}.bind( this ) ).fail( function( xhr, status, err ) {
		dfd.reject( xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : '' );
	}.bind( this ) );
	return dfd.promise();
};