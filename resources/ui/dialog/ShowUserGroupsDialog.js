bs.util.registerNamespace( 'bs.usermanager.ui.dialog' );

bs.usermanager.ui.dialog.ShowUserGroupsDialog = function ( cfg ) {
	bs.usermanager.ui.dialog.ShowUserGroupsDialog.parent.call( this, cfg );
	this.groups = cfg.groups || {};
	this.user = cfg.user || '';
};

OO.inheritClass( bs.usermanager.ui.dialog.ShowUserGroupsDialog, OO.ui.ProcessDialog );

bs.usermanager.ui.dialog.ShowUserGroupsDialog.static.name = 'showGroupsDialog';
bs.usermanager.ui.dialog.ShowUserGroupsDialog.static.title = mw.msg( 'bs-usermanager-showgroups-title' );
bs.usermanager.ui.dialog.ShowUserGroupsDialog.static.actions = [
	{ label: mw.msg( 'bs-usermanager-cancel' ), flags: [ 'safe', 'close' ] }
];

bs.usermanager.ui.dialog.ShowUserGroupsDialog.prototype.initialize = function () {
	bs.usermanager.ui.dialog.ShowUserGroupsDialog.parent.prototype.initialize.call( this );

	this.content = new OO.ui.PanelLayout( {
		expanded: false,
		padded: true
	} );

	const infoLabel = new OO.ui.LabelWidget( {
		label: mw.msg( 'bs-usermanager-showgroups-user-label', this.user, this.groups.length )
	} );
	this.content.$element.append( infoLabel.$element );

	const $ul = $( '<ul>' );
	$.each( this.groups, ( i, group ) => { // eslint-disable-line no-jquery/no-each-util
		const url = mw.Title.makeTitle( -1, 'UserManager/' + group.raw ).getUrl( {
			backTo: mw.config.get( 'wgPageName' )
		} );
		$ul.append( '<li><a href=' + url + '>' + group.displayName + '</a></li>' );
	} );
	this.content.$element.append( $ul );
	this.$body.append( this.content.$element );
};
