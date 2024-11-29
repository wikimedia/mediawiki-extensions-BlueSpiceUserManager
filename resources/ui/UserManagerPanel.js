bs.util.registerNamespace( 'bs.usermanager.ui' );

bs.usermanager.ui.UserManagerPanel = function( cfg ) {
	cfg = cfg || {};
	this.permissions = cfg.permissions || {};
	this.bucketsInitialized = false;
	this.showDisabledActive = false;

	const columns = {
		user_name: {
			type: 'text',
			headerText: mw.msg( 'bs-usermanager-headerusername' ),
			valueParser: function( value, row ) {
				const $a = $( '<a>' ).attr( 'href', row.page_url ).text( value );
				if ( !row.enabled ) {
					const $disabledPill = $( '<span>' )
						.addClass( 'um-disabled-pill' )
						.text( mw.msg( 'bs-usermanager-disabled' ) );
					return new OO.ui.HtmlSnippet( $a[0].outerHTML + $disabledPill[0].outerHTML );
				}
				return new OO.ui.HtmlSnippet( $a[0].outerHTML );
			},
			filter: {
				type: 'string'
			},
			sortable: true
		},
		user_real_name: {
			type: 'text',
			headerText: mw.msg( 'bs-usermanager-headerrealname' ),
			filter: { type: 'text' },
			sortable: true
		},
		user_email: {
			type: 'text',
			headerText: mw.msg( 'bs-usermanager-headeremail' ),
			filter: { type: 'text' },
			sortable: true
		},
		user_registration: {
			type: 'date',
			headerText: mw.msg( 'bs-usermanager-headerregistration' ),
			filter: { type: 'date' },
			sortable: true,
			hidden: true
		},
		groups: {
			type: 'text',
			headerText: mw.msg( 'bs-usermanager-headergroups' ),
			filter: { type: 'list' },
			sortable: false,
			valueParser: function( value, row ) {
				var pills = [];
				$.each( value, function( i, group ) {
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
	if ( this.isAllowed( 'editpassword' ) ) {
		columns.actionChangePassword = {
			type: 'action',
			title: mw.message( 'bs-usermanager-editpassword' ).text(),
			actionId: 'editpassword',
			icon: 'key',
			headerText: mw.message( 'bs-usermanager-editpassword' ).text(),
			invisibleHeader: true,
			width: 30,
			visibleOnHover: true
		};
	}
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
				var options = [];
				for ( var groupKey in buckets.groups ) {
					if ( !buckets.groups.hasOwnProperty( groupKey ) ) {
						continue;
					}
					options.push( { data: groupKey, label: buckets.groups[groupKey] } );
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

bs.usermanager.ui.UserManagerPanel.prototype.getToolbarActions = function() {
	var actions = [];
	actions.push( this.getAddAction( { icon: 'userAdd', displayBothIconAndLabel: true } ) );
	actions.push( this.getEditAction( { icon: 'userRights', displayBothIconAndLabel: true } ) );
	if ( this.isAllowed( 'editpassword' ) ) {
		actions.push( new OOJSPlus.ui.toolbar.tool.ToolbarTool( {
			name: 'editpassword',
			displayBothIconAndLabel: true,
			icon: 'key',
			title: mw.msg( 'bs-usermanager-editpassword' )
		} ) );
	}
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
		title: mw.msg( 'bs-usermanager-titleenableuser' )
	} ) );
	actions.push( new OOJSPlus.ui.toolbar.tool.ToolbarTool( {
		name: 'disableuser',
		displayBothIconAndLabel: true,
		icon: 'block',
		hidden: true,
		title: mw.msg( 'bs-usermanager-titledisableuser' )
	} ) );
	mw.hook( 'usermanager.toolbar.init' ).fire( actions );

	const manager = this;
	actions.push( new OOJSPlus.ui.toolbar.tool.ToolbarTool( {
		name: 'showDisabled',
		icon: 'block',
		displayBothIconAndLabel: true,
		title: mw.msg( 'bs-usermanager-showdisabled' ),
		callback: function() {
			manager.showDisabledActive = !manager.showDisabledActive;
			const filterFactory = new OOJSPlus.ui.data.FilterFactory();
			manager.store.filter( filterFactory.makeFilter( {
				type: 'boolean',
				value: !manager.showDisabledActive
			} ), 'enabled' );
			this.setActive( manager.showDisabledActive );
		}
	} ) );

	return actions;
};

bs.usermanager.ui.UserManagerPanel.prototype.onAction = function( action, row ) {
	var selected = this.grid.getSelectedRows();
	if ( action === 'edit' && ( selected.length === 1 || row ) ) {
		this.editUser( row || selected[0] );
	}
	if ( action === 'add' ) {
		this.addUser();
	}
	if ( action === 'editpassword' && ( selected.length === 1 || row ) ) {
		this.editPassword( row || selected[0] );
	}
	if ( action === 'usergroups' && selected.length > 0 ) {
		this.editGroups( selected );
	}
	if ( action === 'disableuser' && selected.length > 0 ) {
		this.disableUsers( selected );
	}
	if ( action === 'enableuser' && selected.length > 0 ) {
		this.enableUsers( selected );
	}
};

bs.usermanager.ui.UserManagerPanel.prototype.getInitialAbilities = function() {
	return {
		add: true,
		edit: false,
		editpassword: false,
		usergroups: false,
		enableuser: false,
		disableuser: false
	};
};

bs.usermanager.ui.UserManagerPanel.prototype.isAllowed = function( action ) {
	return this.permissions[ action ];
};

bs.usermanager.ui.UserManagerPanel.prototype.onInitialize = function () {
	bs.usermanager.ui.UserManagerPanel.parent.prototype.onInitialize.apply( this, arguments );
	this.toolbar.getTool( 'enableuser' ).toggle( false );
	this.toolbar.getTool( 'disableuser' ).toggle( false );
};

bs.usermanager.ui.UserManagerPanel.prototype.onItemSelected = function ( item, selectedItems ) {
	this.setAbilitiesOnSelection( selectedItems );
};

bs.usermanager.ui.UserManagerPanel.prototype.setAbilitiesOnSelection = function( selectedItems ) {
	if ( selectedItems.length === 1 ) {
		this.setAbilities( { edit: true, editpassword: true, usergroups: true, enableuser: true, disableuser: true } );
	} else if ( selectedItems.length > 1 ) {
		this.setAbilities( { edit: false, editpassword: false, usergroups: true, enableuser: true, disableuser: true } );
	} else {
		this.setAbilities( { edit: false, editpassword: false, usergroups: false, enableuser: false, disableuser: false } );
		this.toolbar.getTool( 'enableuser' ).toggle( false );
		this.toolbar.getTool( 'disableuser' ).toggle( false );
		return;
	}
	// Check if all selected rows are enabled, disabled or mixed (selectedItem.enabled is a boolean)
	var enabled = selectedItems[ 0 ].enabled;
	var mixed = false;
	for ( var i = 1; i < selectedItems.length; i++ ) {
		if ( selectedItems[ i ].enabled !== enabled ) {
			mixed = true;
			break;
		}
	}
	if ( mixed ) {
		this.toolbar.getTool( 'enableuser' ).toggle( false );
		this.toolbar.getTool( 'disableuser' ).toggle( true );
	} else if ( enabled ) {
		this.toolbar.getTool( 'enableuser' ).toggle( false );
		this.toolbar.getTool( 'disableuser' ).toggle( true );
	} else {
		this.toolbar.getTool( 'enableuser' ).toggle( true );
		this.toolbar.getTool( 'disableuser' ).toggle( false );
	}
};

bs.usermanager.ui.UserManagerPanel.prototype.addUser = function() {
	var dialog = new bs.usermanager.ui.dialog.AddUserDialog(
		this.getUserDetailsDialogData( 'add' )
	);
	this.openWindow( dialog );
};

bs.usermanager.ui.UserManagerPanel.prototype.editUser = function( row ) {
	var dialog = new bs.usermanager.ui.dialog.EditUserDialog(
		this.getUserDetailsDialogData( 'edit', row )
	);
	this.openWindow( dialog );
};

bs.usermanager.ui.UserManagerPanel.prototype.editPassword = function( row ) {
	var dialog = new bs.usermanager.ui.dialog.ResetPasswordDialog({
		username: row.user_name,
		email: row.user_email || '',
		changeOwn: mw.config.get( 'wgUserName' ) === row.user_name
	} );
	this.openWindow( dialog );
};

bs.usermanager.ui.UserManagerPanel.prototype.disableUsers = function( rows ) {
	var users = [];
	rows.forEach( function( row ) {
		users.push( row.user_name );
	} );
	OO.ui.confirm( mw.msg( 'bs-usermanager-confirmdisableuser', users.length ) ).done( function( confirmed ) {
		if ( confirmed ) {
			this.doDisableEnableUsers( users, 'PUT' );
		}
	}.bind( this ) );
};

bs.usermanager.ui.UserManagerPanel.prototype.enableUsers = function( rows ) {
	var users = [];
	rows.forEach( function( row ) {
		users.push( row.user_name );
	} );
	OO.ui.confirm( mw.msg( 'bs-usermanager-confirmenableuser', users.length ) ).done( function( confirmed ) {
		if ( confirmed ) {
			this.doDisableEnableUsers( users, 'DELETE' );
		}
	}.bind( this ) );
};

bs.usermanager.ui.UserManagerPanel.prototype.doDisableEnableUsers = function( users, action ) {
	$.ajax( {
		url: mw.util.wikiScript( 'rest' ) + '/bs-usermanager/v1/block',
		method: action,
		data: JSON.stringify( { users: users } ),
		dataType: 'json',
		contentType: 'application/json'
	} ).done( function() {
		this.store.reload();
	}.bind( this ) ).fail( function( xhr, status, err ) {
		this.$element.prepend( new OO.ui.MessageWidget(
			{ type: 'error', label: xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : err }
		).$element );
	} );
};

bs.usermanager.ui.UserManagerPanel.prototype.editGroups = function( rows ) {
	let users = [];
	let groups = [];
	rows.forEach( function( row ) {
		users.push( row.user_name );
		// Intersect with previous value
		groups = groups.length ? groups.filter( function( n ) {
			return row.groups.indexOf( n ) !== -1;
		} ) : row.groups;
	} );
	groups = groups.filter( function( item, pos ) {
		return groups.indexOf( item ) === pos;
	} );
	var dialog = new bs.usermanager.ui.dialog.EditGroupsDialog( {
		users: users,
		groups: groups
	} );
	this.openWindow( dialog );
};

bs.usermanager.ui.UserManagerPanel.prototype.openWindow = function( dialog ) {
	if ( !this.windowManager ) {
		this.windowManager = new OO.ui.WindowManager();
		$( 'body' ).append( this.windowManager.$element );
	}
	this.windowManager.addWindows( [ dialog ] );
	this.windowManager.openWindow( dialog ).closed.then( function( data ) {
		if ( data && data.reload ) {
			this.store.reload();
		}
		this.windowManager.clearWindows();
	}.bind( this ) );
};

bs.usermanager.ui.UserManagerPanel.prototype.getUserDetailsDialogData = function( action, row ) {
	row = row || {};
	return {
		username: row.user_name || '',
		realName: row.user_real_name || '',
		email: row.user_email || '',
		enabled: row.hasOwnProperty( 'enabled' ) ? row.enabled : true,
		groups: row.groups || [],
		canEditGroups: this.isAllowed( 'usergroups' ),
		isCreation: action === 'add'
	};
};
