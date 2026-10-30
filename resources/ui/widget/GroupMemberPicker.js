bs.util.registerNamespace( 'bs.usermanager.ui.widget' );

bs.usermanager.ui.widget.GroupMemberPicker = function ( cfg ) {
	cfg = cfg || {};
	this.selectedUser = null;
	this.ignoreChange = false;
	this.groups = cfg.groups || null;
	this.excludeGroups = cfg.excludeGroups || null;
	this.menuItemConfig = cfg.menuItemConfig || {};

	bs.usermanager.ui.widget.GroupMemberPicker.parent.call( this, Object.assign( {}, cfg, {
		autocomplete: false
	} ) );

	this.lookupMenu.connect( this, {
		toggle: 'onLookupMenuToggle'
	} );

	this.$element.addClass( 'bs-usermanager-group-member-picker' );

	this.connect( this, { change: 'deselectUser' } );
};

OO.inheritClass( bs.usermanager.ui.widget.GroupMemberPicker, mw.widgets.UserInputWidget );

bs.usermanager.ui.widget.GroupMemberPicker.prototype.getSelectedUser = function () {
	return this.selectedUser;
};

bs.usermanager.ui.widget.GroupMemberPicker.prototype.deselectUser = function () {
	if ( this.ignoreChange ) {
		return;
	}
	this.selectedUser = null;
	if ( this.value !== '' ) {
		this.setValidityFlag( false );
	}
};

bs.usermanager.ui.widget.GroupMemberPicker.prototype.abortRequest = function () {
	// DO NOTHING
};

bs.usermanager.ui.widget.GroupMemberPicker.prototype.onLookupMenuToggle = function () {
	if ( this.lookupInputFocused ) {
		this.focus();
	}
};

bs.usermanager.ui.widget.GroupMemberPicker.prototype.onLookupInputFocus = function () {
	if ( this.lookupInputFocused ) {
		return;
	}

	return bs.usermanager.ui.widget.GroupMemberPicker.parent.prototype.onLookupInputFocus.call( this );
};

bs.usermanager.ui.widget.GroupMemberPicker.prototype.focus = function () {
	this.$input.trigger( 'focus' );
};

bs.usermanager.ui.widget.GroupMemberPicker.prototype.getLookupCacheDataFromResponse = function ( response ) {
	if ( !this.allowSuggestionsWhenEmpty && this.$input.val() === '' ) {
		return [];
	}
	if ( response && response.hasOwnProperty( 'results' ) ) {
		return response.results;
	}
	return [];
};

bs.usermanager.ui.widget.GroupMemberPicker.prototype.getValidity = function () {
	if ( this.selectedUser ) {
		return $.Deferred().resolve().promise();
	}
	if ( this.isRequired() ) {
		return $.Deferred().reject().promise();
	}
	return $.Deferred().resolve().promise();
};

bs.usermanager.ui.widget.GroupMemberPicker.prototype.onLookupMenuChoose = function ( item ) {
	this.closeLookupMenu();
	this.setLookupsDisabled( true );
	this.ignoreChange = true;
	bs.usermanager.ui.widget.GroupMemberPicker.parent.prototype.setValue.call( this, item.getDisplayName() );
	this.selectedUser = item;
	this.emit( 'choose', item );
	this.ignoreChange = false;
	this.setLookupsDisabled( false );
};

bs.usermanager.ui.widget.GroupMemberPicker.prototype.onEdit = function () {
	const value = this.$input.val();
	if ( value !== this.value ) {
		this.value = value;
		this.emit( 'change', value );
	}
};

bs.usermanager.ui.widget.GroupMemberPicker.prototype.getValue = function () {
	if ( this.selectedUser ) {
		return this.selectedUser.getUsername();
	}
	return bs.usermanager.ui.widget.GroupMemberPicker.parent.prototype.getValue.call( this );
};

bs.usermanager.ui.widget.GroupMemberPicker.prototype.getLookupRequest = function () {
	const inputValue = this.value;
	const filters = [ {
		type: 'boolean',
		value: true,
		operator: '==',
		property: 'enabled'
	} ];

	if ( this.excludeGroups ) {
		filters.push( {
			type: 'list',
			value: this.excludeGroups,
			operator: 'nct',
			property: 'groups'
		} );
	}
	if ( this.groups ) {
		filters.push( {
			type: 'list',
			value: this.groups,
			operator: 'in',
			property: 'groups'
		} );
	}

	return this.makeLookup( {
		query: inputValue,
		filter: JSON.stringify( filters ),
		limit: inputValue !== '' ? 10 : 5
	} );
};

bs.usermanager.ui.widget.GroupMemberPicker.prototype.makeLookup = function ( data ) {
	return $.ajax( {
		method: 'GET',
		url: mw.util.wikiScript( 'rest' ) + '/bs-usermanager/v1/user-store',
		data: data,
		contentType: 'application/json',
		dataType: 'json'
	} );
};

bs.usermanager.ui.widget.GroupMemberPicker.prototype.getLookupMenuOptionsFromData = function ( data ) {
	const items = [];
	let len, i, user;

	for ( i = 0, len = data.length; i < len; i++ ) {
		user = data[ i ] || {};
		items.push( new OOJSPlus.ui.widget.UserMenuOptionWidget( Object.assign( {}, this.menuItemConfig, user ) ) );
	}
	return items;
};
