bs.util.registerNamespace( 'bs.usermanager.ui.dialog' );

bs.usermanager.ui.dialog.GroupNameDialog = function ( cfg ) {
	bs.usermanager.ui.dialog.GroupNameDialog.parent.call( this, cfg );

	this.group = cfg.group;
};

OO.inheritClass( bs.usermanager.ui.dialog.GroupNameDialog, OO.ui.ProcessDialog );

bs.usermanager.ui.dialog.GroupNameDialog.static.actions = [
	{ action: 'save', label: mw.msg( 'bs-permissionmanager-save' ), flags: [ 'primary', 'progressive' ] },
	{ action: 'close', label: mw.msg( 'bs-permissionmanager-cancel' ), flags: 'safe' }
];

bs.usermanager.ui.dialog.GroupNameDialog.prototype.initialize = function () {
	bs.usermanager.ui.dialog.GroupNameDialog.parent.prototype.initialize.apply( this, arguments );

	this.panel = new OO.ui.PanelLayout( {
		padded: true
	} );

	this.input = new OO.ui.TextInputWidget( {
		value: this.group,
		required: true
	} );

	this.layout = new OO.ui.FieldLayout( this.input, {
		label: mw.msg( 'bs-permissionmanager-group-name' )
	} );

	this.panel.$element.append( this.layout.$element );
	this.$body.append( this.panel.$element );
};

bs.usermanager.ui.dialog.GroupNameDialog.prototype.getActionProcess = function ( action ) {
	if ( action === 'save' ) {
		return new OO.ui.Process( () => {
			const dfd = $.Deferred();
			this.pushPending();
			this.input.getValidity().done( () => {
				const value = this.input.getValue();
				$.ajax( {
					url: this.getUrl( value ),
					type: this.getMethod(),
					success: () => {
						dfd.resolve();
						this.close( { action: 'save', newGroup: value } );
					},
					error: ( xhr ) => {
						this.popPending();
						if ( xhr.hasOwnProperty( 'responseJSON' ) ) {
							dfd.reject(
								new OO.ui.Error( xhr.responseJSON.message || mw.msg( 'bs-permissionmanager-error' ) )
							);
						} else {
							dfd.reject();
						}
					}
				} );
			} ).fail( () => {
				this.popPending();
				this.input.setValidityFlag( false );
				dfd.reject();
			} );

			return dfd.promise();
		} );
	}
	if ( action === 'close' ) {
		this.close( { action: 'cancel' } );
	}

	return bs.usermanager.ui.dialog.GroupNameDialog.parent.prototype.getActionProcess.call( this, action );
};

bs.usermanager.ui.dialog.GroupNameDialog.prototype.getUrl = function ( value ) { // eslint-disable-line no-unused-vars
	return '';
};

bs.usermanager.ui.dialog.GroupNameDialog.prototype.getMethod = function () {
	return '';
};

bs.usermanager.ui.dialog.GroupNameDialog.prototype.getBodyHeight = function () {
	if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
		return this.$element.find( '.oo-ui-processDialog-errors' )[ 0 ].scrollHeight;
	}
	return this.$body[ 0 ].scrollHeight + 20;
};

bs.usermanager.ui.dialog.GroupNameDialog.prototype.onDismissErrorButtonClick = function () {
	this.hideErrors();
	this.updateSize();
};

bs.usermanager.ui.dialog.GroupNameDialog.prototype.showErrors = function () {
	bs.usermanager.ui.dialog.GroupNameDialog.parent.prototype.showErrors.call( this, arguments );
	this.updateSize();
};
