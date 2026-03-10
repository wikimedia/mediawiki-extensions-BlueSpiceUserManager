bs.util.registerNamespace( 'bs.usermanager.ui' );

require( './UserPanel.js' );
require( './GroupsPanel.js' );

bs.usermanager.ui.UserManagementPanel = function () {
	bs.usermanager.ui.UserManagementPanel.parent.call( this, {
		expanded: false,
		framed: false
	} );

	this.makeTabs();
	if ( location.hash ) {
		const tab = location.hash.replace( '#', '' );
		this.setTabPanel( tab );
	}
};

OO.inheritClass( bs.usermanager.ui.UserManagementPanel, OO.ui.IndexLayout );

bs.usermanager.ui.UserManagementPanel.prototype.makeTabs = function () {
	this.groupContent = new OO.ui.TabPanelLayout( 'groups', {
		label: mw.msg( 'bs-usermanager-tab-label-groups' ),
		expanded: false
	} );
	this.groupsPanel = new bs.usermanager.ui.GroupsPanel( {
		tab: this.groupContent
	} );
	this.groupContent.$element.append( this.groupsPanel.$element );

	this.userContent = new OO.ui.TabPanelLayout( 'users', {
		label: mw.msg( 'bs-usermanager-tab-label-users' ),
		expanded: false
	} );
	this.userPanel = new bs.usermanager.ui.UserPanel( {
		permissions: mw.config.get( 'bsUserManagerPermissions' ),
		tab: this.userContent
	} );
	this.userContent.$element.append( this.userPanel.$element );
	this.addTabPanels( [ this.groupContent, this.userContent ] );

	this.connect( this, {
		set: ( page ) => {
			location.hash = page.getName();
		}
	} );
};
