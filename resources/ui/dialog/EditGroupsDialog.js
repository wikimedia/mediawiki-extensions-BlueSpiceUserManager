bs.util.registerNamespace( 'bs.usermanager.ui.dialog' );

bs.usermanager.ui.dialog.EditGroupsDialog = function( cfg ) {
	bs.usermanager.ui.dialog.EditGroupsDialog.parent.call( this, cfg );
	this.users = cfg.users;
	this.groups = cfg.groups;
};

OO.inheritClass( bs.usermanager.ui.dialog.EditGroupsDialog, OO.ui.ProcessDialog );

bs.usermanager.ui.dialog.EditGroupsDialog.static.name = 'editGroupsDialog';
bs.usermanager.ui.dialog.EditGroupsDialog.static.title = mw.msg( 'bs-usermanager-editgroups' );
bs.usermanager.ui.dialog.EditGroupsDialog.static.actions = [
	{ action: 'save', label: mw.msg( 'bs-usermanager-save' ), flags: [ 'primary', 'progressive' ] },
	{ action: 'cancel', label: mw.msg( 'bs-usermanager-cancel' ), flags: [ 'safe' ] }
];

bs.usermanager.ui.dialog.EditGroupsDialog.prototype.initialize = function() {
	bs.usermanager.ui.dialog.EditGroupsDialog.parent.prototype.initialize.call( this );

	this.content = new OO.ui.PanelLayout( {
		expanded: false,
		padded: true
	} );

	this.groupInput = new OOJSPlus.ui.widget.GroupMultiSelectWidget( {
		$overlay: this.$overlay,
	} );
	this.groupInput.setValue( this.groups );
	this.groupInput.connect( this, { change: 'updateSize' } );

	this.content.$element.append(
		new OO.ui.FieldLayout( this.groupInput, {
			label: mw.msg( 'bs-usermanager-headergroups' ),
			align: 'top'
		} ).$element
	);
	this.$body.append( this.content.$element );
};

bs.usermanager.ui.dialog.EditGroupsDialog.prototype.getActionProcess = function( action ) {
	return bs.usermanager.ui.dialog.EditGroupsDialog.parent.prototype.getActionProcess.call( this, action ).next(
		async function() {
			if ( action === 'save' ) {
				let data = {};
				var dfd = $.Deferred();
				this.pushPending();
				try {
					await this.saveData( { users: this.users, groups: this.groupInput.getValue() } );
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

bs.usermanager.ui.dialog.EditGroupsDialog.prototype.saveData = async function( data ) {
	var dfd = $.Deferred();
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/bs-usermanager/v1/groups',
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

bs.usermanager.ui.dialog.EditGroupsDialog.prototype.getBodyHeight = function () {
	if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
		return this.$element.find( '.oo-ui-processDialog-errors' )[0].scrollHeight;
	}
	return this.$body[0].scrollHeight;
};

bs.usermanager.ui.dialog.EditGroupsDialog.prototype.onDismissErrorButtonClick = function () {
	this.hideErrors();
	this.updateSize();
};

bs.usermanager.ui.dialog.EditGroupsDialog.prototype.showErrors = function () {
	bs.usermanager.ui.dialog.EditGroupsDialog.parent.prototype.showErrors.call( this, arguments );
	this.updateSize();
};