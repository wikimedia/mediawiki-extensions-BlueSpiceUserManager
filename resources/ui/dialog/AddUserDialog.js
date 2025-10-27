bs.util.registerNamespace( 'bs.usermanager.ui.dialog' );

bs.usermanager.ui.dialog.AddUserDialog = function ( cfg ) {
	cfg.isCreation = true;
	bs.usermanager.ui.dialog.AddUserDialog.parent.call( this, cfg );
};

OO.inheritClass( bs.usermanager.ui.dialog.AddUserDialog, bs.usermanager.ui.dialog.EditUserDialog );

bs.usermanager.ui.dialog.AddUserDialog.static.name = 'addUserDialog';
bs.usermanager.ui.dialog.AddUserDialog.static.title = mw.msg( 'bs-usermanager-titleadduser' );
bs.usermanager.ui.dialog.AddUserDialog.static.actions = [
	{ action: 'save', label: mw.msg( 'bs-usermanager-save' ), flags: [ 'primary', 'progressive' ], disabled: true },
	{ action: 'cancel', label: mw.msg( 'bs-usermanager-cancel' ), flags: [ 'safe', 'close' ] }
];

bs.usermanager.ui.dialog.AddUserDialog.prototype.getContentPanel = function () {
	return new bs.usermanager.ui.AddUserPanel( {
		$overlay: this.$overlay
	} );
};

bs.usermanager.ui.dialog.AddUserDialog.prototype.saveData = function ( data ) {
	const username = data.username;
	delete data.username;
	const dfd = $.Deferred();
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/bs-usermanager/v1/user/create/' + username,
		method: 'POST',
		data: JSON.stringify( data ),
		dataType: 'json',
		contentType: 'application/json'
	} ).done( () => {
		dfd.resolve();
	} ).fail( ( xhr ) => {
		dfd.reject( xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : '' );
	} );
	return dfd.promise();
};
