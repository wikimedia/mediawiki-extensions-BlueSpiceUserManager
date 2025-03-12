bs.util.registerNamespace( 'bs.usermanager.ui' );

bs.usermanager.ui.UserManagerPanel = function ( cfg ) {
	cfg = cfg || {};
	this.permissions = cfg.permissions || {};
	this.bucketsInitialized = false;

	const columns = {
		user_name: { // eslint-disable-line camelcase
			type: 'text',
			headerText: mw.msg( 'bs-usermanager-headerusername' ),
			valueParser: function ( value, row ) {
				const $a = $( '<a>' ).attr( 'href', row.page_url ).text( value );
				if ( !row.enabled ) {
					const $disabledPill = $( '<span>' )
						.addClass( 'um-disabled-pill' )
						.text( mw.msg( 'bs-usermanager-disabled' ) );
					return new OO.ui.HtmlSnippet( $a[ 0 ].outerHTML + $disabledPill[ 0 ].outerHTML );
				}
				return new OO.ui.HtmlSnippet( $a[ 0 ].outerHTML );
			},
			filter: {
				type: 'string'
			},
			sortable: true
		},
		user_real_name: { // eslint-disable-line camelcase
			type: 'text',
			headerText: mw.msg( 'bs-usermanager-headerrealname' ),
			filter: { type: 'text' },
			sortable: true
		},
		user_email: { // eslint-disable-line camelcase
			type: 'text',
			headerText: mw.msg( 'bs-usermanager-headeremail' ),
			filter: { type: 'text' },
			sortable: true
		},
		groups: {
			type: 'text',
			headerText: mw.msg( 'bs-usermanager-headergroups' ),
			filter: { type: 'tag_list' },
			sortable: false,
			valueParser: function ( value ) {
				const pills = [];
				$.each( value, ( i, group ) => { // eslint-disable-line no-jquery/no-each-util
					pills.push( '<span class="um-group-pill">' + group + '</span>' );
				} );

				return new OO.ui.HtmlSnippet( pills );
			},
			maxWidth: 300
		},
		actionEdit: {
			type: 'action',
			title: mw.message( 'bs-usermanager-titleeditdetails' ).text(),
			actionId: 'edit',
			icon: 'edit',
			headerText: mw.message( 'bs-usermanager-titleeditdetails' ).text(),
			invisibleHeader: true,
			width: 30,
			visibleOnHover: true
		}
	};
	const subActions = [];
	if ( this.isAllowed( 'editpassword' ) ) {
		subActions.push( {
			label: mw.message( 'bs-usermanager-editpassword' ).text(),
			data: 'editpassword',
			icon: 'key'
		} );
	}
	subActions.push( {
		label: mw.message( 'bs-usermanager-titledisableuser' ).text(),
		data: 'disableuser',
		icon: 'block',
		shouldShow: function ( row ) {
			return row.enabled;
		}
	} );
	subActions.push( {
		label: mw.message( 'bs-usermanager-titleenableuser' ).text(),
		data: 'enableuser',
		icon: 'unBlock',
		shouldShow: function ( row ) {
			return !row.enabled;
		}
	} );
	columns.others = {
		type: 'secondaryActions',
		visibleOnHover: true,
		actions: subActions
	};
	this.store = new OOJSPlus.ui.data.store.RemoteRestStore( {
		path: 'mws/v1/user-query-store',
		filter: {
			enabled: {
				type: 'boolean',
				value: true
			}
		}
	} );
	this.store.connect( this, {
		loaded: function () {
			// Reset previously selected abilities when grid is reloaded
			this.setAbilitiesOnSelection( [] );
			if ( this.bucketsInitialized ) {
				return;
			}
			const buckets = this.store.getBuckets();
			if ( buckets.hasOwnProperty( 'groups' ) && this.grid.columns.groups.filter ) {
				// Convert key value to `{data: key, label: value }`
				const options = [];
				for ( const groupKey in buckets.groups ) {
					if ( !buckets.groups.hasOwnProperty( groupKey ) ) {
						continue;
					}
					options.push( { data: groupKey, label: buckets.groups[ groupKey ] } );
				}
				this.grid.columns.groups.filter.setOptions( options );
			}
			this.bucketsInitialized = true;
		}
	} );
	cfg.grid = {
		store: this.store,
		columns: columns
	};
	bs.usermanager.ui.UserManagerPanel.parent.call( this, cfg );
};

OO.inheritClass( bs.usermanager.ui.UserManagerPanel, OOJSPlus.ui.panel.ManagerGrid );

bs.usermanager.ui.UserManagerPanel.prototype.getToolbarActions = function () {
	const actions = [];
	actions.push( this.getAddAction( { icon: 'userAdd', flags: [ 'progressive' ], displayBothIconAndLabel: true } ) );
	if ( this.isAllowed( 'usergroups' ) ) {
		actions.push( new OOJSPlus.ui.toolbar.tool.ToolbarTool( {
			name: 'usergroups',
			displayBothIconAndLabel: true,
			icon: 'userContributions',
			title: mw.msg( 'bs-usermanager-editgroups' )
		} ) );
	}
	actions.push( new OOJSPlus.ui.toolbar.tool.ToolbarTool( {
		name: 'enableuser',
		displayBothIconAndLabel: true,
		icon: 'unBlock',
		hidden: true,
		title: mw.msg( 'bs-usermanager-mass-enable' )
	} ) );
	actions.push( new OOJSPlus.ui.toolbar.tool.ToolbarTool( {
		name: 'disableuser',
		displayBothIconAndLabel: true,
		icon: 'block',
		hidden: true,
		title: mw.msg( 'bs-usermanager-mass-disable' )
	} ) );
	mw.hook( 'usermanager.toolbar.init' ).fire( actions );

	const manager = this;
	actions.push( new OOJSPlus.ui.toolbar.tool.ToolbarTool( {
		name: 'showEnabled',
		position: 'right',
		displayBothIconAndLabel: true,
		title: mw.msg( 'bs-usermanager-showenabled' ),
		callback: function () {
			const filterFactory = new OOJSPlus.ui.data.FilterFactory();
			manager.store.filter( filterFactory.makeFilter( {
				type: 'boolean',
				value: true
			} ), 'enabled' );
			this.setActive( false );
			this.toggle( false );
			manager.toolbar.getTool( 'showDisabled' ).toggle( true );
			manager.toolbar.getTool( 'enableuser' ).toggle( false );
			manager.toolbar.getTool( 'disableuser' ).toggle( true );
		}
	} ) );
	actions.push( new OOJSPlus.ui.toolbar.tool.ToolbarTool( {
		name: 'showDisabled',
		position: 'right',
		displayBothIconAndLabel: true,
		title: mw.msg( 'bs-usermanager-showdisabled' ),
		callback: function () {
			const filterFactory = new OOJSPlus.ui.data.FilterFactory();
			manager.store.filter( filterFactory.makeFilter( {
				type: 'boolean',
				value: false
			} ), 'enabled' );
			this.setActive( false );
			this.toggle( false );
			manager.toolbar.getTool( 'enableuser' ).toggle( true );
			manager.toolbar.getTool( 'disableuser' ).toggle( false );
			manager.toolbar.getTool( 'showEnabled' ).toggle( true );
		}
	} ) );

	return actions;
};

bs.usermanager.ui.UserManagerPanel.prototype.onAction = function ( action, row ) {
	const selected = this.grid.getSelectedRows();
	if ( action === 'edit' && ( selected.length === 1 || row ) ) {
		this.editUser( row || selected[ 0 ] );
	}
	if ( action === 'add' ) {
		this.addUser();
	}
	if ( action === 'editpassword' && ( selected.length === 1 || row ) ) {
		this.editPassword( row || selected[ 0 ] );
	}
	if ( action === 'usergroups' && selected.length > 0 ) {
		this.editGroups( selected );
	}
	if ( action === 'disableuser' && ( selected.length > 0 || row ) ) {
		this.disableUsers( row ? [ row ] : selected );
	}
	if ( action === 'enableuser' && ( selected.length > 0 || row ) ) {
		this.enableUsers( row ? [ row ] : selected );
	}
};

bs.usermanager.ui.UserManagerPanel.prototype.getInitialAbilities = function () {
	return {
		add: true,
		usergroups: false,
		enableuser: false,
		disableuser: false
	};
};

bs.usermanager.ui.UserManagerPanel.prototype.isAllowed = function ( action ) {
	return this.permissions[ action ];
};

bs.usermanager.ui.UserManagerPanel.prototype.onInitialize = function () {
	bs.usermanager.ui.UserManagerPanel.parent.prototype.onInitialize.apply( this, arguments );
	this.toolbar.getTool( 'enableuser' ).toggle( false );
	this.toolbar.getTool( 'showEnabled' ).toggle( false );
};

bs.usermanager.ui.UserManagerPanel.prototype.onItemSelected = function ( item, selectedItems ) {
	this.setAbilitiesOnSelection( selectedItems );
};

bs.usermanager.ui.UserManagerPanel.prototype.setAbilitiesOnSelection = function ( selectedItems ) {
	if ( selectedItems.length === 1 ) {
		this.setAbilities( { usergroups: true, enableuser: true, disableuser: true } );
	} else if ( selectedItems.length > 1 ) {
		this.setAbilities( { usergroups: true, enableuser: true, disableuser: true } );
	} else {
		this.setAbilities( { usergroups: false, enableuser: false, disableuser: false } );
	}
};

bs.usermanager.ui.UserManagerPanel.prototype.addUser = function () {
	const dialog = new bs.usermanager.ui.dialog.AddUserDialog(
		this.getUserDetailsDialogData( 'add' )
	);
	this.openWindow( dialog );
};

bs.usermanager.ui.UserManagerPanel.prototype.editUser = function ( row ) {
	const dialog = new bs.usermanager.ui.dialog.EditUserDialog(
		this.getUserDetailsDialogData( 'edit', row )
	);
	this.openWindow( dialog );
};

bs.usermanager.ui.UserManagerPanel.prototype.editPassword = function ( row ) {
	const dialog = new bs.usermanager.ui.dialog.ResetPasswordDialog( {
		username: row.user_name,
		email: row.user_email || '',
		changeOwn: mw.config.get( 'wgUserName' ) === row.user_name
	} );
	this.openWindow( dialog );
};

bs.usermanager.ui.UserManagerPanel.prototype.disableUsers = function ( rows ) {
	const users = [];
	rows.forEach( ( row ) => {
		users.push( row.user_name );
	} );
	OO.ui.confirm( mw.msg( 'bs-usermanager-confirmdisableuser', users.length ) ).done( ( confirmed ) => {
		if ( confirmed ) {
			this.doDisableEnableUsers( users, 'create' );
		}
	} );
};

bs.usermanager.ui.UserManagerPanel.prototype.enableUsers = function ( rows ) {
	const users = [];
	rows.forEach( ( row ) => {
		users.push( row.user_name );
	} );
	OO.ui.confirm( mw.msg( 'bs-usermanager-confirmenableuser', users.length ) ).done( ( confirmed ) => {
		if ( confirmed ) {
			this.doDisableEnableUsers( users, 'delete' );
		}
	} );
};

bs.usermanager.ui.UserManagerPanel.prototype.doDisableEnableUsers = function ( users, action ) {
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/bs-usermanager/v1/block/' + action,
		method: 'POST',
		data: JSON.stringify( { users: users } ),
		dataType: 'json',
		contentType: 'application/json'
	} ).done( () => {
		this.store.reload();
	} ).fail( function ( xhr, status, err ) {
		this.$element.prepend( new OO.ui.MessageWidget(
			{ type: 'error', label: xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : err }
		).$element );
	} );
};

bs.usermanager.ui.UserManagerPanel.prototype.editGroups = function ( rows ) {
	const users = [];
	let groups = [];
	rows.forEach( ( row ) => {
		users.push( row.user_name );
		// Intersect with previous value
		groups = groups.length ? groups.filter( ( n ) => row.groups_raw.indexOf( n ) !== -1 ) : row.groups_raw;
	} );
	groups = groups.filter( ( item, pos ) => groups.indexOf( item ) === pos );
	const dialog = new bs.usermanager.ui.dialog.EditGroupsDialog( {
		users: users,
		groups: groups
	} );
	this.openWindow( dialog );
};

bs.usermanager.ui.UserManagerPanel.prototype.openWindow = function ( dialog ) {
	if ( !this.windowManager ) {
		this.windowManager = new OO.ui.WindowManager();
		$( 'body' ).append( this.windowManager.$element );
	}
	this.windowManager.addWindows( [ dialog ] );
	this.windowManager.openWindow( dialog ).closed.then( ( data ) => {
		if ( data && data.reload ) {
			this.store.reload();
		}
		this.windowManager.clearWindows();
	} );
};

bs.usermanager.ui.UserManagerPanel.prototype.getUserDetailsDialogData = function ( action, row ) {
	row = row || {};

	return {
		username: row.user_name || '',
		realName: row.user_real_name || '',
		email: row.user_email || '',
		enabled: row.hasOwnProperty( 'enabled' ) ? row.enabled : true,
		groups: row.groups_raw || [],
		canEditGroups: this.isAllowed( 'usergroups' ),
		isCreation: action === 'add'
	};
};
