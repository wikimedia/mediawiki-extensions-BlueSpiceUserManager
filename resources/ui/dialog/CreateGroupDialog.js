bs.util.registerNamespace( 'bs.usermanager.ui.dialog' );

bs.usermanager.ui.dialog.CreateGroupDialog = function ( config ) {
	config = config || {};
	bs.usermanager.ui.dialog.CreateGroupDialog.parent.call( this, config );
};

OO.inheritClass( bs.usermanager.ui.dialog.CreateGroupDialog, OO.ui.ProcessDialog );

bs.usermanager.ui.dialog.CreateGroupDialog.static.name = 'createGroupDialog';
bs.usermanager.ui.dialog.CreateGroupDialog.static.title = mw.msg( 'bs-usermanager-create-groups-dialog-title' );
bs.usermanager.ui.dialog.CreateGroupDialog.static.actions = [
	{ action: 'close', label: mw.message( 'bs-usermanager-cancel' ).text(), flags: [ 'safe', 'close' ] },
	{
		action: 'submit',
		label: mw.message( 'bs-usermanager-action-create' ).text(),
		flags: [ 'progressive', 'primary' ]
	}
];

bs.usermanager.ui.dialog.CreateGroupDialog.prototype.getSetupProcess = function () {
	return bs.usermanager.ui.dialog.CreateGroupDialog.parent.prototype.getSetupProcess.call( this ).next( function () {
		this.actions.setAbilities( { submit: false, close: true } );
	}, this );
};

bs.usermanager.ui.dialog.CreateGroupDialog.prototype.initialize = function () {
	bs.usermanager.ui.dialog.CreateGroupDialog.parent.prototype.initialize.apply( this, arguments );
	this.panel = new OO.ui.PanelLayout( {
		padded: true,
		expanded: false
	} );
	this.actions.setAbilities( { submit: false } );
	this.addItems();
	this.$body.append( this.panel.$element );
};

bs.usermanager.ui.dialog.CreateGroupDialog.prototype.addItems = function () {
	this.nameInput = new OO.ui.TextInputWidget( {
		required: true,
		maxLength: 200
	} );
	this.panel.$element.append(
		new OO.ui.FieldLayout( this.nameInput, {
			label: mw.message( 'bs-usermanager-create-groups-group-name' ).text(),
			align: 'top'
		} ).$element
	);
	this.nameInput.connect( this, {
		change: function () {
			this.checkValidity();
		}
	} );
};

bs.usermanager.ui.dialog.CreateGroupDialog.prototype.getActionProcess = function ( action ) {
	if ( action === 'close' ) {
		return new OO.ui.Process( function () {
			this.close();
		}, this );
	}
	if ( action === 'submit' ) {
		return new OO.ui.Process( function () {
			const dfd = $.Deferred();
			this.actions.setAbilities( { submit: false, close: false } );
			this.pushPending();
			this.checkValidity().done( () => {
				const name = this.nameInput.getValue();
				$.ajax( {
					url: mw.util.wikiScript( 'rest' ) + '/bs-usermanager/v1/groups/create/' + encodeURIComponent( name ),
					type: 'POST',
					success: ( data ) => {
						this.close( { action: 'submit', name: data.groupname } );
						dfd.resolve();
					},
					error: ( jqXHR ) => {
						const error = JSON.parse( jqXHR.responseText );
						this.showErrors( new OO.ui.Error( error.message, { recoverable: false } ) );
						this.actions.setAbilities( { close: true } );
						this.popPending();
						dfd.reject();
					}
				} );
			} ).fail( () => {
				this.actions.setAbilities( { close: true } );
				this.popPending();
			} );

			return dfd.promise();
		}, this );
	}
	return bs.usermanager.ui.dialog.CreateGroupDialog.parent.prototype.getActionProcess.call( this, action );
};

bs.usermanager.ui.dialog.CreateGroupDialog.prototype.checkValidity = function () {
	const dfd = $.Deferred();
	this.nameInput.getValidity().done( () => {
		this.actions.setAbilities( { submit: true } );
		dfd.resolve();
	} ).fail( () => {
		this.actions.setAbilities( { submit: false } );
		dfd.reject();
	} );
	return dfd.promise();
};

bs.usermanager.ui.dialog.CreateGroupDialog.prototype.getBodyHeight = function () {
	if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
		return this.$element.find( '.oo-ui-processDialog-errors' )[ 0 ].scrollHeight;
	}
	return this.$element.find( '.oo-ui-window-body' )[ 0 ].scrollHeight;
};

bs.usermanager.ui.dialog.CreateGroupDialog.prototype.showErrors = function ( errors ) {
	bs.usermanager.ui.dialog.CreateGroupDialog.parent.prototype.showErrors.call( this, errors );
	this.updateSize();
};
