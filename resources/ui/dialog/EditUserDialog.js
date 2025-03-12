bs.util.registerNamespace( 'bs.usermanager.ui.dialog' );

bs.usermanager.ui.dialog.EditUserDialog = function ( cfg ) {
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

bs.usermanager.ui.dialog.EditUserDialog.prototype.initialize = function () {
	bs.usermanager.ui.dialog.EditUserDialog.parent.prototype.initialize.call( this );
	this.content = this.getContentPanel();
	this.content.connect( this, {
		change: 'updateSize',
		validityCheck: 'onValidityCheck'
	} );
	this.content.initialize();
	this.$body.append( this.content.$element );
};

bs.usermanager.ui.dialog.EditUserDialog.prototype.getContentPanel = function () {
	return new bs.usermanager.ui.UserDetailsPanel( {
		isCreation: this.isCreation,
		username: this.username,
		realName: this.realName,
		email: this.email,
		enabled: this.enabled,
		groups: this.groups,
		$overlay: this.$overlay
	} );
};

bs.usermanager.ui.dialog.EditUserDialog.prototype.onValidityCheck = function ( valid ) {
	this.actions.setAbilities( { save: valid } );
};

bs.usermanager.ui.dialog.EditUserDialog.prototype.getActionProcess = function ( action ) {
	if ( action === 'save' ) {
		return new OO.ui.Process( function () {
			const dfd = $.Deferred();
			this.pushPending();
			this.getValidData().done( ( data ) => {
				this.saveData( data ).done( () => {
					this.close( { reload: true } );
				} ).fail( ( e ) => {
					this.popPending();
					if ( !e ) {
						dfd.reject( new OO.ui.Error( mw.msg( 'bs-usermanager-error-generic' ) ) );
					} else {
						dfd.reject( new OO.ui.Error( e ) );
					}
				} );
			} ).fail( () => {
				this.popPending();
				dfd.resolve();
			} );
			return dfd.promise();
		}, this );
	}
	if ( action === 'cancel' ) {
		return new OO.ui.Process( function () {
			this.close( { reload: false } );
		}, this );
	}
	return bs.usermanager.ui.dialog.EditUserDialog.parent.prototype.getActionProcess.call( this, action );
};

bs.usermanager.ui.dialog.EditUserDialog.prototype.getValidData = function () {
	return this.content.getValidData();
};

bs.usermanager.ui.dialog.EditUserDialog.prototype.saveData = function ( data ) {
	const dfd = $.Deferred();
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/bs-usermanager/v1/user/edit/' + this.username,
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

bs.usermanager.ui.dialog.EditUserDialog.prototype.getBodyHeight = function () {
	if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
		return this.$element.find( '.oo-ui-processDialog-errors' )[ 0 ].scrollHeight;
	}
	return this.$body[ 0 ].scrollHeight;
};

bs.usermanager.ui.dialog.EditUserDialog.prototype.onDismissErrorButtonClick = function () {
	this.hideErrors();
	this.updateSize();
};

bs.usermanager.ui.dialog.EditUserDialog.prototype.showErrors = function () {
	bs.usermanager.ui.dialog.EditUserDialog.parent.prototype.showErrors.call( this, arguments );
	this.updateSize();
};
