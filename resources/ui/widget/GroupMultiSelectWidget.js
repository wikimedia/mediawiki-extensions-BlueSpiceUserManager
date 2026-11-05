bs.util.registerNamespace( 'bs.usermanager.ui.widget' );

bs.usermanager.ui.widget.GroupMultiSelectWidget = function ( config ) {
	bs.usermanager.ui.widget.GroupMultiSelectWidget.parent.call( this, config );
};

OO.inheritClass( bs.usermanager.ui.widget.GroupMultiSelectWidget, OOJSPlus.ui.widget.GroupMultiSelectWidget );

bs.usermanager.ui.widget.GroupMultiSelectWidget.prototype.getLookupDataFromResponse = function ( response ) {
	if ( Array.isArray( response ) ) {
		return response;
	}
	if ( response && response.hasOwnProperty( 'results' ) && Array.isArray( response.results ) ) {
		return response.results;
	}
	return [];
};

bs.usermanager.ui.widget.GroupMultiSelectWidget.prototype.makeLookup = function ( data ) {
	return $.ajax( {
		method: 'GET',
		url: mw.util.wikiScript( 'rest' ) + '/bs-usermanager/v1/group-store',
		data: data,
		contentType: 'application/json',
		dataType: 'json'
	} );
};

bs.usermanager.ui.widget.GroupMultiSelectWidget.prototype.queryGroups = function ( data ) {
	const dfd = $.Deferred();
	this.makeLookup( data ).done( ( response ) => {
		dfd.resolve( this.getLookupDataFromResponse( response ) );
	} ).fail( () => {
		dfd.reject();
	} );
	return dfd.promise();
};

bs.usermanager.ui.widget.GroupMultiSelectWidget.prototype.getGroupData = function ( group ) {
	if ( typeof group === 'object' ) {
		if ( group.hasOwnProperty( 'group_name' ) ) {
			group = group.group_name;
		} else {
			group = '';
		}
	}
	if ( !group ) {
		return $.Deferred().resolve().promise();
	}
	const dfd = $.Deferred();
	this.queryGroups( {
		query: group,
		limit: 10
	} ).done( ( groups ) => {
		const match = groups.find( ( item ) => item.group_name === group );
		dfd.resolve( match || { group_name: group } ); // eslint-disable-line camelcase
	} ).fail( () => {
		dfd.resolve( { group_name: group } ); // eslint-disable-line camelcase
	} );

	return dfd.promise();
};

/**
 * Update autocomplete menu with items
 *
 * @private
 */
bs.usermanager.ui.widget.GroupMultiSelectWidget.prototype.updateMenuItems = function () {
	const inputValue = this.input.getValue();

	if ( inputValue === this.inputValue ) {
		// Do not restart api query if nothing has changed in the input
		return;
	} else {
		this.inputValue = inputValue;
	}

	const filters = [];
	if ( this.groupTypes.length > 0 ) {
		filters.push( {
			type: 'list',
			property: 'group_type',
			value: this.groupTypes,
			operator: 'in'
		} );
	}

	this.pushPending();

	this.queryGroups( {
		query: inputValue,
		filter: JSON.stringify( filters ),
		allowEveryone: this.allowEveryoneOption
	} ).done( ( response ) => {
		const selectedGroups = this.getSelectedGroups();
		// Remove groups which are already selected from suggestions
		const suggestions = response.map( ( group ) => {
			if ( !selectedGroups.includes( group.group_name ) ) {
				// This is necessary in order to match actual group names
				return new OO.ui.MenuOptionWidget( {
					data: group.group_name,
					label: group.displayname || group.group_name,
					id: group.group_name
				} );
			}
			return undefined;
		} ).filter( ( item ) => item !== undefined );

		// Remove all items from menu add fill it with new
		this.menu.clearItems();
		this.menu.addItems( suggestions );

		if ( suggestions.length ) {
			// Enable Narrator focus on menu item, see T250762.
			this.menu.$focusOwner.attr( 'aria-activedescendant', suggestions[ 0 ].$element.attr( 'id' ) );
		}

		// Make the menu visible; it might not be if it was previously empty
		this.menu.toggle( true );

		this.popPending();
	} ).fail( ( e ) => {
		this.popPending();
		console.error( e ); // eslint-disable-line no-console
	} );
};
