bs.util.registerNamespace( 'bs.usermanager.ui' );

require( './dialog/AddGroupMemberDialog.js' );

bs.usermanager.ui.GroupDetailsPanel = function ( cfg ) {
	cfg = cfg || {};
	cfg.padded = false;
	this.groupName = cfg.groupName;
	const columns = {
		username: {
			type: 'user',
			headerText: mw.msg( 'bs-usermanager-group-details-header-user' ),
			filter: {
				type: 'user'
			},
			valueParser: function ( value ) {
				return new OOJSPlus.ui.widget.UserWidget( {
					user_name: value // eslint-disable-line camelcase
				} );
			},
			sortable: true
		},
		actionDelete: {
			type: 'action',
			title: mw.message( 'bs-usermanager-group-details-header-action-remove-member' ).text(),
			actionId: 'deleteMember',
			icon: 'close',
			headerText: mw.message( 'bs-usermanager-group-details-header-action-remove-member' ).text(),
			invisibleHeader: true,
			width: 30,
			destructive: true,
			visibleOnHover: true
		}
	};

	this.store = new OOJSPlus.ui.data.store.RemoteRestStore( {
		path: 'bs-usermanager/v1/groups/members/' + this.groupName
	} );
	cfg.grid = {
		store: this.store,
		columns: columns
	};
	this.externalFilter = new OOJSPlus.ui.data.grid.ExternalFilter( {
		store: this.store,
		sort: {
			value: 'name',
			sortOptions: [
				{ data: 'name', label: mw.msg( 'bs-usermanager-group-details-sort-user-name' ) }
			],
			direction: 'asc'
		}
	} );
	bs.usermanager.ui.GroupDetailsPanel.parent.call( this, cfg );

	const $memberLabel = $( '<h2>' ).text( mw.msg( 'bs-usermanager-group-details-section-members' ) );
	$memberLabel.insertAfter( this.toolbar.$element );
	this.externalFilter.$element.insertBefore( this.grid.$element );
	this.$element.removeClass( 'oo-ui-panelLayout-padded' );
};
OO.inheritClass( bs.usermanager.ui.GroupDetailsPanel, OOJSPlus.ui.panel.ManagerGrid );

bs.usermanager.ui.GroupDetailsPanel.prototype.getToolbarActions = function () {
	return [
		this.getAddAction( {
			flags: [ 'progressive' ],
			title: mw.msg( 'bs-usermanager-group-details-section-members-action-add-user' ),
			displayBothIconAndLabel: true
		} ),
		this.getDeleteAction( {
			title: mw.msg( 'bs-usermanager-group-details-action-delete-group' ),
			displayBothIconAndLabel: true
		} )
	];
};

bs.usermanager.ui.GroupDetailsPanel.prototype.getInitialAbilities = function () {
	return {
		add: true,
		delete: true
	};
};

bs.usermanager.ui.GroupDetailsPanel.prototype.onAction = function ( action, row ) {
	if ( action === 'add' ) {
		this.openDialog(
			new bs.usermanager.ui.dialog.AddGroupMemberDialog( { group: this.groupName } ),
			( data ) => {
				if ( data.action === 'submit' ) {
					this.store.reload();
				}
			}
		);
	}
	if ( action === 'delete' ) {
		OO.ui.confirm( mw.msg( 'bs-usermanager-group-details-confirm-delete-group', this.groupName ),
			{
				actions: [
					{
						label: mw.msg( 'bs-usermanager-group-details-button-action-label-delete' ),
						flags: [ 'destructive' ],
						action: 'accept'
					},
					{
						label: mw.msg( 'bs-usermanager-cancel' ),
						action: 'cancel'
					}
				]
			} )
			.then( ( confirmed ) => {
				if ( confirmed ) {
					$.ajax( {
						url: mw.util.wikiScript( 'rest' ) +
							'/bs-usermanager/v1/groups/delete/' + encodeURIComponent( this.groupName ),
						type: 'POST'
					} ).then( () => {
						window.location.href = mw.Title.makeTitle( -1, 'UserManager' ).getUrl();
					}, () => {
						OO.ui.alert( mw.msg( 'bs-usermanager-group-details-error-delete-group' ) );
					} );
				}
			} );
	}
	if ( action === 'deleteMember' ) {
		OO.ui.confirm( mw.msg( 'bs-usermanager-groups-confirm-remove-member', row.username ),
			{
				actions: [
					{
						label: mw.msg( 'bs-usermanager-action-label-remove' ),
						flags: [ 'destructive' ],
						action: 'accept'
					},
					{
						label: mw.msg( 'bs-usermanager-cancel' ),
						action: 'cancel'
					}
				]
			} )
			.then( ( confirmed ) => {
				if ( confirmed ) {
					$.ajax( {
						url: mw.util.wikiScript( 'rest' ) +
							'/bs-usermanager/v1/groups/unassign/' + encodeURIComponent( this.groupName ),
						type: 'POST',
						data: JSON.stringify( {
							user: row.username
						} ),
						dataType: 'json',
						contentType: 'application/json; charset=UTF-8'
					} ).then( () => {
						this.store.reload();
					}, () => {
						OO.ui.alert( mw.msg( 'bs-usermanager-groups-error-remove-member' ) );
					} );
				}
			} );
	}
};

bs.usermanager.ui.GroupDetailsPanel.prototype.openDialog = function ( dialog, callback ) {
	const wm = OO.ui.getWindowManager();
	wm.addWindows( [ dialog ] );
	wm.openWindow( dialog ).closed.then( callback );
};
