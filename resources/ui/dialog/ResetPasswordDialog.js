bs.util.registerNamespace( 'bs.usermanager.ui.dialog' );

bs.usermanager.ui.dialog.ResetPasswordDialog = function( cfg ) {
	bs.usermanager.ui.dialog.ResetPasswordDialog.parent.call( this, cfg );
	this.username = cfg.username;
	this.email = cfg.email;
	this.changeOwn = cfg.changeOwn;
};

OO.inheritClass( bs.usermanager.ui.dialog.ResetPasswordDialog, OO.ui.ProcessDialog );

bs.usermanager.ui.dialog.ResetPasswordDialog.static.name = 'resetPasswordDialog';
bs.usermanager.ui.dialog.ResetPasswordDialog.static.title = mw.msg( 'bs-usermanager-editpassword' );
bs.usermanager.ui.dialog.ResetPasswordDialog.static.actions = [
	{ action: 'save', label: mw.msg( 'bs-usermanager-save' ), flags: [ 'primary', 'progressive' ] },
	{ action: 'cancel', label: mw.msg( 'bs-usermanager-cancel' ), flags: [ 'safe' ] }
];

bs.usermanager.ui.dialog.ResetPasswordDialog.prototype.initialize = function() {
	bs.usermanager.ui.dialog.ResetPasswordDialog.parent.prototype.initialize.call( this );

	this.content = new OO.ui.PanelLayout( {
		expanded: false,
		padded: true
	} );

	if ( this.changeOwn ) {
		this.content.$element.append( new OO.ui.MessageWidget( {
			type: 'warning',
			label: mw.msg( 'bs-usermanager-warning-password-change-own' )
		} ).$element );
	}

	this.strategySelector = new OO.ui.RadioSelectWidget( {
		items: [
			new OO.ui.RadioOptionWidget( {
				data: 'reset',
				label: mw.msg( 'bs-usermanager-label-password-change-strategy-reset' ),
				disabled: !this.email
			} ),
			new OO.ui.RadioOptionWidget( {
				data: 'password',
				label: mw.msg( 'bs-usermanager-label-password-change-strategy-pw' )
			} )
		]
	} );
	this.strategySelector.connect( this, { select: 'onStrategySelect' } );

	this.$passwordPanel = $( '<div>' );
	this.$passwordPanel.hide();

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
	this.$passwordPanel.append(
		new OO.ui.FieldLayout( this.passwordInput, {
			label: mw.msg( 'bs-usermanager-labelnewpassword' ),
			align: 'left'
		} ).$element,
		new OO.ui.FieldLayout( this.passwordRepeatInput, {
			label: mw.msg( 'bs-usermanager-labelpasswordcheck' ),
			align: 'left'
		} ).$element
	);

	this.content.$element.append(
		new OO.ui.FieldLayout( this.strategySelector, {
			label: mw.msg( 'bs-usermanager-label-password-change-strategy' ),
			align: 'left'
		} ).$element,
		this.$passwordPanel
	);
	this.$body.append( this.content.$element );

	this.strategySelector.selectItem( this.strategySelector.findFirstSelectableItem() );
};

bs.usermanager.ui.dialog.ResetPasswordDialog.prototype.onStrategySelect = function( item ) {
	this.$passwordPanel.toggle( item.getData() === 'password' );
	this.updateSize();
};

bs.usermanager.ui.dialog.ResetPasswordDialog.prototype.getValidData = function() {
	const dfd = $.Deferred();
	if ( this.strategySelector.findSelectedItem().getData() !== 'password' ) {
		dfd.resolve( { strategy: this.strategySelector.findSelectedItem().getData() } );
		return dfd.promise();
	}
	let data = {
		strategy: 'password'
	};
	this.passwordInput.getValidity().done( function() {
		this.passwordRepeatInput.getValidity().done( function() {
			data.password = this.passwordInput.getValue();
			data.repassword = this.passwordRepeatInput.getValue();
			dfd.resolve( data );
		}.bind( this ) ).fail( function() {
			this.passwordInput.setValidityFlag( false );
			this.passwordRepeatInput.setValidityFlag( false );
			dfd.reject();
		}.bind( this ) );
	}.bind( this ) ).fail( function() {
		this.passwordInput.setValidityFlag( false );
		this.passwordRepeatInput.setValidityFlag( false );
		dfd.reject();
	}.bind( this ) );
	return dfd.promise();
};

bs.usermanager.ui.dialog.ResetPasswordDialog.prototype.getActionProcess = function( action ) {
	return bs.usermanager.ui.dialog.ResetPasswordDialog.parent.prototype.getActionProcess.call( this, action ).next(
		function() {
			if ( action === 'save' ) {
				var dfd = $.Deferred();
				this.pushPending();
				this.getValidData().done( function( data ) {
					this.saveData( data ).done( function() {
						this.close( { reload: true } );
					}.bind( this ) ).fail( function( e ) {
						this.popPending();
						if ( !e ) {
							dfd.reject( new OO.ui.Error( mw.msg( 'bs-usermanager-error-generic' ) ) );
						} else {
							dfd.reject( new OO.ui.Error( e ) );
						}
					}.bind( this ) );
				}.bind( this ) ).fail( function() {
					this.popPending();
					dfd.resolve();
				}.bind( this ) );
				return dfd.promise();
			} else {
				this.close( { reload: false } );
			}
		}, this
	);
};

bs.usermanager.ui.dialog.ResetPasswordDialog.prototype.saveData = function( data ) {
	const dfd = $.Deferred();
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/bs-usermanager/v1/password/' + this.username,
		method: 'POST',
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

bs.usermanager.ui.dialog.ResetPasswordDialog.prototype.getBodyHeight = function () {
	if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
		return this.$element.find( '.oo-ui-processDialog-errors' )[0].scrollHeight;
	}
	return this.$body[0].scrollHeight;
};

bs.usermanager.ui.dialog.ResetPasswordDialog.prototype.onDismissErrorButtonClick = function () {
	this.hideErrors();
	this.updateSize();
};

bs.usermanager.ui.dialog.ResetPasswordDialog.prototype.showErrors = function () {
	bs.usermanager.ui.dialog.ResetPasswordDialog.parent.prototype.showErrors.call( this, arguments );
	this.updateSize();
};