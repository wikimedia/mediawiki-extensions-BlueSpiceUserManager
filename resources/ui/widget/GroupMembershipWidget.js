bs.util.registerNamespace( 'bs.usermanager.ui.widget' );

require( './../dialog/ShowUserGroupsDialog.js' );

bs.usermanager.ui.widget.GroupMembershipWidget = function ( cfg ) {
	cfg = cfg || {};
	this.type = cfg.type || 'user'; // type user or group
	this.countMemberships = cfg.count || 0; // number of memberships
	this.url = cfg.url || ''; // url if button should contain href
	this.membershipList = cfg.membershipList || [];
	this.infoAbout = cfg.infoAbout || ''; // username or groupname
	this.rawValues = cfg.rawValues || [];

	bs.usermanager.ui.widget.GroupMembershipWidget.parent.call( this, {} );
	this.build();
	this.$element.addClass( 'bs-usermanager-membership-info' );
};

OO.inheritClass( bs.usermanager.ui.widget.GroupMembershipWidget, OO.ui.Widget );

bs.usermanager.ui.widget.GroupMembershipWidget.static.tagName = 'div';

bs.usermanager.ui.widget.GroupMembershipWidget.prototype.build = function () {
	// The following messages are used here:
	// * bs-usermanager-member-user-count
	// * bs-usermanager-member-group-count
	const $groupMsg = $( '<span>' ).append( mw.msg( 'bs-usermanager-member-' + this.type + '-count', this.countMemberships ) );
	this.$element.append( $groupMsg );

	if ( this.countMemberships === 0 ) {
		return;
	}
	// The following messages are used here:
	// * bs-usermanager-member-user-info-label
	// * bs-usermanager-member-group-info-label
	this.infoBtn = new OO.ui.ButtonWidget( {
		framed: false,
		icon: 'infoFilled',
		label: mw.msg( 'bs-usermanager-member-' + this.type + '-info-label' ),
		invisibleLabel: true,
		href: this.url
	} );
	if ( this.url ) {
		$( this.infoBtn ).attr( 'role', 'link' );
	}
	this.$element.append( this.infoBtn.$element );

	if ( this.type === 'user' ) {
		this.infoBtn.connect( this, {
			click: 'showMembershipInfo'
		} );
	}
};

bs.usermanager.ui.widget.GroupMembershipWidget.prototype.showMembershipInfo = function () {
	const groups = [];
	for ( let i = 0; i < this.rawValues.length; i++ ) {
		groups.push( {
			displayName: this.membershipList[ i ],
			raw: this.rawValues[ i ]
		} );
	}
	const dialog = new bs.usermanager.ui.dialog.ShowUserGroupsDialog( {
		groups: groups,
		user: this.infoAbout
	} );
	this.openWindow( dialog );
};

bs.usermanager.ui.widget.GroupMembershipWidget.prototype.openWindow = function ( dialog ) {
	if ( !this.windowManager ) {
		this.windowManager = new OO.ui.WindowManager();
		$( 'body' ).append( this.windowManager.$element );
	}
	this.windowManager.addWindows( [ dialog ] );
	this.windowManager.openWindow( dialog ).closed.then( () => {
		this.windowManager.clearWindows();
	} );
};
