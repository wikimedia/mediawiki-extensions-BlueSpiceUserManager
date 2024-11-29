bs.util.registerNamespace( 'bs.usermanager.ui.dialog' );

bs.usermanager.ui.dialog.EditUserDialog = function( cfg ) {
	bs.usermanager.ui.dialog.EditUserDialog.parent.call( this, cfg );
	this.isCreation = cfg.isCreation || false;
	this.username = cfg.username;
	this.realName = cfg.realName || '';
	this.email = cfg.email || '';
	this.enabled = cfg.enabled || false;
	this.groups = cfg.groups || [];
};

OO.inheritClass( bs.usermanager.ui.dialog.EditUserDialog, OO.ui.ProcessDialog );

bs.usermanager.ui.dialog.EditUserDialog.static.name = 'editUserDialog';
bs.usermanager.ui.dialog.EditUserDialog.static.title = mw.msg( 'bs-usermanager-titleeditdetails' );
bs.usermanager.ui.dialog.EditUserDialog.static.actions = [
	{ action: 'save', label: mw.msg( 'bs-usermanager-save' ), flags: [ 'primary', 'progressive' ] },
	{ action: 'cancel', label: mw.msg( 'bs-usermanager-cancel' ), flags: [ 'safe' ] }
];

bs.usermanager.ui.dialog.EditUserDialog.prototype.initialize = function() {
	bs.usermanager.ui.dialog.EditUserDialog.parent.prototype.initialize.call( this );
	this.content = new bs.usermanager.ui.UserDetailsPanel( {
		isCreation: this.isCreation,
		username: this.username,
		realName: this.realName,
		email: this.email,
		enabled: this.enabled,
		groups: this.groups,
		$overlay: this.$overlay
	} );
	this.content.connect( this, { change: 'updateSize' } );
	this.content.initialize();
	this.$body.append( this.content.$element );
};

bs.usermanager.ui.dialog.EditUserDialog.prototype.getActionProcess = function( action ) {
	return bs.usermanager.ui.dialog.EditUserDialog.parent.prototype.getActionProcess.call( this, action ).next(
		async function() {
			if ( action === 'save' ) {
				let data = {};
				var dfd = $.Deferred();
				this.pushPending();
				try {
					data = await this.getValidData();
				} catch ( e ) {
					this.popPending();
					dfd.resolve();
					return dfd.promise();
				}
				try {
					await this.saveData( data );
					this.close( { reload: true } );
				} catch ( e ) {
					this.popPending();
					if ( !e ) {
						dfd.reject();
					} else {
						dfd.reject( new OO.ui.Error( e ) );
					}
				}
				return dfd.promise();
			} else {
				this.close( { reload: false } );
			}
		}, this
	);
};

bs.usermanager.ui.dialog.EditUserDialog.prototype.getValidData = async function() {
	return this.content.getValidData();
};

bs.usermanager.ui.dialog.EditUserDialog.prototype.saveData = async function( data ) {
	var dfd = $.Deferred();
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/bs-usermanager/v1/user/' + this.username,
		method: 'POST',
		data: JSON.stringify( data ),
		dataType: 'json',
		contentType: 'application/json'
	} ).done( function() {
		dfd.resolve();
	}.bind( this ) ).fail( function( err, status, xhr ) {
		dfd.reject( xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : '' );
	}.bind( this ) );
	return dfd.promise();
};

bs.usermanager.ui.dialog.EditUserDialog.prototype.getBodyHeight = function () {
	if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
		return this.$element.find( '.oo-ui-processDialog-errors' )[0].scrollHeight;
	}
	return this.$body[0].scrollHeight;
};

bs.usermanager.ui.dialog.EditUserDialog.prototype.onDismissErrorButtonClick = function () {
	this.hideErrors();
	this.updateSize();
};

bs.usermanager.ui.dialog.EditUserDialog.prototype.showErrors = function () {
	bs.usermanager.ui.dialog.EditUserDialog.parent.prototype.showErrors.call( this, arguments );
	this.updateSize();
};