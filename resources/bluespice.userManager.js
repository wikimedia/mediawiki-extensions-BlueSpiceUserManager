$( () => {

	require( './ui/UserManagementPanel.js' );
	require( './ui/GroupDetailsPanel.js' );

	const $userManagerCnt = $( '#bs-usermanager-grid' );
	if ( $userManagerCnt.length ) {
		const panel = new bs.usermanager.ui.UserManagementPanel();
		$userManagerCnt.append( panel.$element );
	}

	const $detailsCnt = $( '#bs-usermanager-group-details' );
	if ( $detailsCnt.length ) {
		$detailsCnt.append( new bs.usermanager.ui.GroupDetailsPanel( {
			groupName: $detailsCnt.data( 'group' )
		} ).$element );
	}
} );
