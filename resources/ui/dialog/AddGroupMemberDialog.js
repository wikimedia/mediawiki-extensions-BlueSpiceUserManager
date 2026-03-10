bs.util.registerNamespace( 'bs.usermanager.ui.dialog' );

bs.usermanager.ui.dialog.AddGroupMemberDialog = function ( config ) {
	config = config || {};
	this.group = config.group || {};
	bs.usermanager.ui.dialog.AddGroupMemberDialog.parent.call( this, config );
};
OO.inheritClass( bs.usermanager.ui.dialog.AddGroupMemberDialog, OO.ui.ProcessDialog );

bs.usermanager.ui.dialog.AddGroupMemberDialog.static.name = 'addGroupMember';

bs.usermanager.ui.dialog.AddGroupMemberDialog.static.title = mw.msg( 'bs-usermanager-groups-action-add-member-title' );

bs.usermanager.ui.dialog.AddGroupMemberDialog.static.actions = [
	{ action: 'close', label: mw.message( 'bs-usermanager-cancel' ).text(), flags: [ 'safe', 'close' ] },
	{
		action: 'submit',
		label: mw.message( 'bs-usermanager-action-label-add' ).text(),
		flags: [ 'progressive', 'primary' ]
	}
];

bs.usermanager.ui.dialog.AddGroupMemberDialog.prototype.getSetupProcess = function () {
	return bs.usermanager.ui.dialog.AddGroupMemberDialog.parent.prototype.getSetupProcess.call( this ).next( function () {
		this.actions.setAbilities( { submit: false, close: true } );
	}, this );
};

bs.usermanager.ui.dialog.AddGroupMemberDialog.prototype.initialize = function () {
	bs.usermanager.ui.dialog.AddGroupMemberDialog.parent.prototype.initialize.apply( this, arguments );
	this.panel = new OO.ui.PanelLayout( {
		padded: true,
		expanded: false
	} );
	this.actions.setAbilities( { submit: false } );
	this.addItems();
	this.$body.append( this.panel.$element );
};

bs.usermanager.ui.dialog.AddGroupMemberDialog.prototype.addItems = function () {
	this.userInput = new OOJSPlus.ui.widget.UserPickerWidget( {
		$overlay: this.$overlay
	} );
	this.panel.$element.append(
		new OO.ui.FieldLayout( this.userInput, {
			label: mw.message( 'bs-usermanager-groups-member-field-user' ).text(),
			align: 'left'
		} ).$element
	);
	this.userInput.connect( this, {
		change: 'checkValidity',
		choose: 'checkValidity'
	} );
};

bs.usermanager.ui.dialog.AddGroupMemberDialog.prototype.getActionProcess = function ( action ) {
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
			const selectedUser = this.userInput.getSelectedUser();
			if ( !selectedUser ) {
				this.popPending();
				return dfd.reject();
			}
			$.ajax( {
				url: mw.util.wikiScript( 'rest' ) + '/bs-usermanager/v1/groups/assign/' + encodeURIComponent( this.group ),
				type: 'POST',
				data: JSON.stringify( {
					user: selectedUser.userWidget.user.user_name
				} ),
				dataType: 'json',
				contentType: 'application/json; charset=UTF-8'
			} ).done( ( data ) => { // eslint-disable-line no-unused-vars
				this.close( { action: 'submit', name: name } );
				dfd.resolve();
			} ).fail( () => {
				this.popPending();
				dfd.reject();
			} );
			return dfd.promise();
		}, this );
	}
	return bs.usermanager.ui.dialog.AddGroupMemberDialog.parent.prototype.getActionProcess.call( this, action );
};

bs.usermanager.ui.dialog.AddGroupMemberDialog.prototype.checkValidity = function () {
	this.actions.setAbilities( { submit: !!this.userInput.getSelectedUser() } );
};

bs.usermanager.ui.dialog.AddGroupMemberDialog.prototype.getBodyHeight = function () {
	if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
		return this.$element.find( '.oo-ui-processDialog-errors' )[ 0 ].scrollHeight;
	}
	return this.$element.find( '.oo-ui-window-body' )[ 0 ].scrollHeight;
};

bs.usermanager.ui.dialog.AddGroupMemberDialog.prototype.showErrors = function ( errors ) {
	bs.usermanager.ui.dialog.AddGroupMemberDialog.parent.prototype.showErrors.call( this, errors );
	this.updateSize();
};
