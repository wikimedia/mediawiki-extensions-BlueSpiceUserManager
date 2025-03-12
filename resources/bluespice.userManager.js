$( () => {
	const panel = new bs.usermanager.ui.UserManagerPanel( {
		permissions: mw.config.get( 'bsUserManagerPermissions' )
	} );
	$( '#bs-usermanager-grid' ).append( panel.$element );
} );
