bs.util.registerNamespace( 'bs.usermanager.ui' );

bs.usermanager.ui.UserDetailsPanel = function ( cfg ) {
	cfg = cfg || {};
	cfg.expanded = false;
	cfg.padded = true;
	bs.usermanager.ui.UserDetailsPanel.parent.call( this, cfg );
	this.$element.addClass( 'bs-usermanager-userdetails' );
	this.isCreation = cfg.isCreation || false;
	this.username = cfg.username || '';
	this.realName = cfg.realName || '';
	this.email = cfg.email || '';
	this.enabled = cfg.enabled !== undefined ? cfg.enabled : true;
	this.groups = cfg.groups || [];
	this.$overlay = cfg.$overlay || true;
};

OO.inheritClass( bs.usermanager.ui.UserDetailsPanel, OO.ui.PanelLayout );

bs.usermanager.ui.UserDetailsPanel.prototype.initialize = function () {
	this.usernameInput = new OO.ui.TextInputWidget( {
		value: this.username,
		classes: [ 'um-username' ],
		disabled: !this.isCreation,
		required: this.isCreation
	} );
	this.usernameInput.connect( this, { change: 'validateOnChange' } );
	this.realNameInput = new OO.ui.TextInputWidget( {
		value: this.realName,
		classes: [ 'um-realname' ]
	} );
	this.emailInput = new OO.ui.TextInputWidget( {
		value: this.email,
		validation: 'email',
		classes: [ 'um-email' ],
		type: 'email'
	} );
	this.emailInput.connect( this, { change: 'validateOnChange' } );
	this.groupInput = new OOJSPlus.ui.widget.GroupMultiSelectWidget( {
		$overlay: this.$overlay,
		groupTypes: [ 'core-minimal', 'explicit', 'custom', 'extension-minimal' ]
	} );

	this.groupInput.setValue( this.groups );
	this.groupInput.connect( this, { change: function () {
		this.emit( 'change' );
	} } );

	this.makeForm();
};

bs.usermanager.ui.UserDetailsPanel.prototype.makeForm = function () {
	const form = new OO.ui.FieldsetLayout( {
		classes: [ 'um-form' ]
	} );
	form.addItems( [
		new OO.ui.FieldLayout( this.usernameInput, {
			label: mw.msg( 'bs-usermanager-headerusername' ),
			align: 'left'
		} ),
		new OO.ui.FieldLayout( this.realNameInput, {
			label: mw.msg( 'bs-usermanager-headerrealname' ),
			align: 'left'
		} ),
		new OO.ui.FieldLayout( this.emailInput, {
			label: mw.msg( 'bs-usermanager-headeremail' ),
			align: 'left'
		} ),
		new OO.ui.FieldLayout( this.groupInput, {
			label: mw.msg( 'bs-usermanager-headergroups' ),
			align: 'left'
		} )
	] );

	this.$element.append( form.$element );
};

bs.usermanager.ui.UserDetailsPanel.prototype.getValidData = function () {
	const dfd = $.Deferred();
	this.checkValidity( [ this.usernameInput, this.realNameInput, this.emailInput ] ).done( () => {
		dfd.resolve( {
			username: this.usernameInput.getValue(),
			realName: this.realNameInput.getValue(),
			email: this.emailInput.getValue(),
			enabled: this.enabled,
			groups: this.groupInput.getValue()
		} );
	} ).fail( () => {
		dfd.reject();
	} );
	return dfd.promise();
};

bs.usermanager.ui.UserDetailsPanel.prototype.validateOnChange = function () {
	this.getValidData().done( () => {
		this.emit( 'validityCheck', true );
	} ).fail( () => {
		this.emit( 'validityCheck', false );
	} );
};

bs.usermanager.ui.UserDetailsPanel.prototype.checkValidity = function ( fields ) {
	const dfd = $.Deferred();
	const promises = fields.map( ( field ) => field.getValidity() );
	$.when( ...promises ).done( () => {
		dfd.resolve();
	} ).fail( () => {
		dfd.reject();
	} );
	return dfd.promise();
};
