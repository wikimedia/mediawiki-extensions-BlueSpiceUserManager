bs.util.registerNamespace( 'bs.usermanager.ui' );

require( './dialog/CreateGroupDialog.js' );
require( './widget/GroupMembershipWidget.js' );

bs.usermanager.ui.GroupsPanel = function ( cfg ) {
	cfg = cfg || {};
	cfg.padded = false;

	const columns = {
		group_name: { // eslint-disable-line camelcase
			type: 'text',
			headerText: mw.msg( 'bs-usermanager-groups-header-group-name' ),
			filter: {
				type: 'string'
			},
			sortable: true,
			valueParser: function ( value, row ) {
				const url = mw.Title.makeTitle( -1, 'UserManager' ).getUrl( {
					group: row.group_name,
					backTo: mw.config.get( 'wgPageName' )
				} );
				const $anchor = '<a href="' + url + '">' + row.displayname + '</a>';
				return new OO.ui.HtmlSnippet( $( '<div>' ).append( $anchor ).html() );
			}
		},
		usercount: {
			width: 180,
			type: 'text',
			headerText: mw.msg( 'bs-usermanager-groups-header-members' ),
			valueParser: function ( value, row ) {
				const url = mw.Title.makeTitle( -1, 'UserManager' ).getUrl( {
					group: row.group_name,
					backTo: mw.config.get( 'wgPageName' )
				} );

				return new bs.usermanager.ui.widget.GroupMembershipWidget( {
					type: 'group',
					count: value,
					url: url
				} );
			}
		},
		actionEdit: {
			type: 'action',
			title: mw.msg( 'bs-usermanager-action-edit-group' ),
			actionId: 'edit',
			icon: 'edit',
			headerText: mw.msg( 'bs-usermanager-action-edit-group' ),
			invisibleHeader: true,
			width: 30,
			visibleOnHover: true
		},
		actionDelete: {
			type: 'action',
			title: mw.msg( 'bs-usermanager-action-delete-group' ),
			actionId: 'delete',
			icon: 'trash',
			headerText: mw.msg( 'bs-usermanager-action-delete-group' ),
			invisibleHeader: true,
			width: 30,
			visibleOnHover: true
		}
	};
	this.store = new OOJSPlus.ui.data.store.RemoteRestStore( {
		path: 'mws/v1/group-store',
		sorter: {
			name: { direction: 'asc' }
		}
	} );
	cfg.grid = {
		store: this.store,
		columns: columns,
		multiSelect: false
	};

	this.externalFilter = new OOJSPlus.ui.data.grid.ExternalFilter( {
		store: this.store,
		sort: {
			value: 'name',
			direction: 'asc'
		}
	} );
	bs.usermanager.ui.GroupsPanel.parent.call( this, cfg );

	this.store.connect( this, {
		loaded: ( values ) => {
			const numberOfGroups = Object.keys( values ).length;
			const $badgeNumer = $( '<span>' ).addClass( 'bs-um-tab-badge' ).text( numberOfGroups );
			this.tab.getTabItem().setLabel(
				new OO.ui.HtmlSnippet( $( '<span>' ).text( mw.msg( 'bs-usermanager-tab-label-groups' ) ).append( $badgeNumer ) )
			);
		}
	} );
	this.tab = cfg.tab;
	this.externalFilter.$element.insertBefore( this.grid.$element );
};

OO.inheritClass( bs.usermanager.ui.GroupsPanel, OOJSPlus.ui.panel.ManagerGrid );

bs.usermanager.ui.GroupsPanel.prototype.getToolbarActions = function () {
	return [
		this.getAddAction( {
			flags: [ 'progressive' ], displayBothIconAndLabel: true,
			title: mw.msg( 'bs-usermanager-action-create-group' )
		} )
	];
};

bs.usermanager.ui.GroupsPanel.prototype.onAction = function ( action, row ) {
	if ( action === 'add' ) {
		this.openDialog(
			new bs.usermanager.ui.dialog.CreateGroupDialog(),
			( data ) => {
				if ( data.action === 'submit' ) {
					window.location.href = mw.Title.makeTitle( -1, 'UserManager' ).getUrl( {
						group: data.name,
						created: true,
						backTo: mw.config.get( 'wgPageName' )
					} );
				}
			}
		);
	}
	if ( action === 'edit' ) {
		if ( !row ) {
			return;
		}
		window.location.href = mw.Title.makeTitle( -1, 'UserManager' ).getUrl( {
			group: row.group_name,
			backTo: mw.config.get( 'wgPageName' )
		} );
	}
	if ( action === 'delete' ) {
		if ( !row ) {
			return;
		}
		OO.ui.confirm( mw.msg( 'bs-usermanager-group-details-confirm-delete-group', row.group_name ), {
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
							'/bs-usermanager/v1/groups/delete/' + encodeURIComponent( row.group_name ),
						type: 'POST'
					} ).then( () => {
						window.location.href = mw.Title.makeTitle( -1, 'UserManager' ).getUrl();
					}, () => {
						OO.ui.alert( mw.msg( 'bs-usermanager-group-details-error-delete-group' ) );
					} );
				}
			} );
	}
};

bs.usermanager.ui.GroupsPanel.prototype.openDialog = function ( dialog, callback ) {
	const wm = OO.ui.getWindowManager();
	wm.addWindows( [ dialog ] );
	wm.openWindow( dialog ).closed.then( callback );
};

bs.usermanager.ui.GroupsPanel.prototype.getInitialAbilities = function () {
	return {
		add: true
	};
};
