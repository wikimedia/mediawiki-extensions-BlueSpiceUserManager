/**
 * UserManager Panel
 *
 * Part of BlueSpice MediaWiki
 *
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @author     Stephan Muggli <muggli@hallowelt.com>
 * @package    Bluespice_Extensions
 * @subpackage UserManager
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 * @filesource
 */

Ext.define( 'BS.UserManager.panel.Manager', {
	extend: 'BS.CRUDGridPanel',
	requires: [ 'BS.UserManager.dialog.AddUser', 'BS.UserManager.dialog.EditUser', 'BS.UserManager.dialog.UserGroups', 'BS.UserManager.dialog.Password' ],
	id: 'bs-usermanager-extgrid',
	features: [],
	initComponent: function() {
		this.strMain = Ext.create( 'BS.store.BSApi', {
			apiAction: 'bs-adminuser-store',
			fields: [
				'user_id',
				'user_name',
				'user_page_link',
				'user_real_name',
				'user_email',
				{ 'name': 'user_registration', 'type': 'date', 'dateFormat': 'YmdHis' },
				'page_link',
				'groups',
				'enabled'
			],
			proxy: {
				reader:{
					idProperty: 'user_id'
			}}
		});

		this.strGroups = Ext.create( 'BS.store.BSApi', {
			apiAction: 'bs-usermanager-group-store',
			fields: ['group_name', 'additional_group', 'displayname'],
			proxy: {
				extraParams: {
					limit: 999999
				}
			},
			submitValue: false
		});

		this.strGroups.on( 'load', function( sender, records, successful, eOpts ){
			for( var i = 0; i < records.length; i++ ) {
				var record = records[i];
				record.set( 'id', record.get( 'group_name' ));
				record.set( 'text', record.get( 'displayname' ));
			}
		}, this );

		this.colEnabled = Ext.create( 'Ext.grid.column.Column', {
			id: 'enabled',
			header: mw.message('bs-usermanager-headerenabled').plain(),
			sortable: true,
			dataIndex: 'enabled',
			hidden: true,
			renderer: this.renderIcon,
			flex: 1,
			xtype: 'booleancolumn',
			filter: {
				type: 'boolean',
				value: true,
				defaultValue: true,
				yesText: mw.message( 'bs-usermanager-filter-only-activated-users' ).plain(),
				noText: mw.message( 'bs-usermanager-filter-only-deactivated-users' ).plain()
			}
		} );
		this.colUserName = Ext.create( 'Ext.grid.column.Template', {
			id: 'username',
			header: mw.message('bs-usermanager-headerusername').plain(),
			sortable: true,
			dataIndex: 'user_name',
			tpl: '{page_link}',
			flex: 1,
			filter: {
				type: 'string'
			}
		} );
		this.colRealName = Ext.create( 'Ext.grid.column.Template', {
			id: 'userrealname',
			header: mw.message('bs-usermanager-headerrealname').plain(),
			sortable: true,
			dataIndex: 'user_real_name',
			tpl: '{user_real_name}',
			flex: 1,
			filter: {
				type: 'string'
			}
		} );
		this.colEmail = Ext.create( 'Ext.grid.column.Column', {
			id: this.getId()+'-useremail',
			header: mw.message('bs-usermanager-headeremail').plain(),
			sortable: true,
			dataIndex: 'user_email',
			renderer: this.renderEmail,
			flex: 1,
			filter: {
				type: 'string'
			}
		} );
		this.colRegistration = Ext.create( 'Ext.grid.column.Column', {
			id: this.getId()+'-userregistration',
			header: mw.message( 'bs-usermanager-headerregistration' ).plain(),
			sortable: true,
			dataIndex: 'user_registration',
			renderer: this.renderRegistration,
			hidden: true,
			flex: 1,
			filter: {
				type: 'date'
			}
		} );
		this.colGroups = Ext.create( 'Ext.grid.column.Column', {
			header: mw.message('bs-usermanager-headergroups').plain(),
			dataIndex: 'groups',
			renderer: this.renderGroups,
			sortable: false,
			flex: 1,
			filter: {
				type: 'string'
			}
		} );

		this.colMainConf.columns = [
			this.colEnabled,
			this.colUserName,
			this.colRealName,
			this.colEmail,
			this.colRegistration,
			this.colGroups
		];
		this.callParent( arguments );
	},

	makeGridPlugins: function() {
		var plugins = this.callParent();
		plugins.push( {
			ptype: 'rowexpander',
			rowBodyTpl : new Ext.XTemplate(
				'<strong>' + mw.message('bs-usermanager-headergroups').plain() + ':</strong> {groups:this.renderGroups}',
				{
					renderGroups: function( value ){
						return value.join( ', ' );
					}
				}
			)
		} );

		return plugins;
	},

	makeSelModel: function(){
		this.smModel = Ext.create( 'Ext.selection.CheckboxModel', {
			mode: "MULTI",
			selType: 'checkboxmodel'
		});
		return this.smModel;
	},

	makeGridColumns: function() {
		var columns = this.callParent(arguments);
		return {
			items: columns.items
		};
	},

	makeTbarItems: function() {
		var arrItems = this.callParent(arguments);
		if(this.opPermitted('usergroups')){
			this.btnEditGroups = new Ext.Button({
				id: this.getId()+'-btn-edit-groups',
				icon: mw.config.get( 'wgScriptPath') + '/extensions/BlueSpiceFoundation/resources/bluespice/images/bs-btn_group.png',
				iconCls: 'btn'+this.tbarHeight,
				tooltip:  mw.message('bs-usermanager-editgroups').plain(),
				height: 50,
				width: 52,
				disabled: true
			});

			this.btnEditGroups.on( 'click', this.onBtnEditGroupsClick, this );
			arrItems.push( this.btnEditGroups );
		}

		if(this.opPermitted('editpassword')) {
			this.btnEditPassword = new Ext.Button({
				id: this.getId()+'-btn-edit-password',
				icon: mw.config.get( 'wgScriptPath') + '/extensions/BlueSpiceFoundation/resources/bluespice/images/bs-btn_password.png',
				iconCls: 'btn'+this.tbarHeight,
				tooltip: mw.message('bs-usermanager-editpassword').plain(),
				height: 50,
				width: 52,
				disabled: true
			});
			this.btnEditPassword.on( 'click', this.onBtnEditPasswordClick, this );
			arrItems.push( this.btnEditPassword );
		}

		return arrItems;
	},
	makeRowActions: function() {
		this.callParent(arguments);

		if(this.opPermitted('disableuser') || this.opPermitted('enableuser')){
			this.colMainConf.actions.unshift({
				iconCls: 'bs-extjs-actioncolumn-icon icon-blocked progressive',
				glyph: true,
				tooltip: mw.message( 'bs-usermanager-endisable' ).plain(),
				handler: this.onActionDisableClick,
				scope: this
			});
		}

		if(this.opPermitted( 'usergroups')){
			this.colMainConf.actions.unshift({
				iconCls: 'bs-extjs-actioncolumn-icon bs-icon-group progressive',
				glyph: true,
				tooltip: mw.message( 'bs-usermanager-editgroups' ).plain(),
				handler: this.onActionEditGroupClick,
				scope: this
			});
		}

		if(this.opPermitted('editpassword')){
			this.colMainConf.actions.unshift({
				iconCls: 'bs-extjs-actioncolumn-icon bs-icon-key progressive',
				glyph: true,
				tooltip: mw.message( 'bs-usermanager-editpassword' ).plain(),
				handler: this.onActionEditPasswordClick,
				scope: this
			});
		}

		return this.colMainConf.actions;
	},

	renderGroups: function( value ) {
		return value.join( ', ' );
	},
	renderEmail: function( value ) {
		if ( value.length === 0 ) return '';

		return '<a href="mailto:' + value + '">' + value + '</a>';
	},
	renderRegistration: function ( value ) {
		if( !value ) {
			return '';
		}
		return value.toLocaleString( mw.user.options.get( 'language' ) );
	},
	renderIcon: function( value ) {
		//TODO: make CSS class icon
		var icon = '<img src="' + mw.config.get( "wgScriptPath" ) + '/extensions/BlueSpiceFoundation/resources/bluespice/images/{0}"/>';
		if ( value === false ) {
			return icon.format( 'bs-cross.png' );
		}
		return icon.format( 'bs-tick.png' );
	},
	onGrdMainRowClick: function( oSender, iRowIndex, oEvent ) {
		this.callParent(arguments);
		/*
		 * We override base class functionality which disables edit button on
		 * multi selection
		 */
		this.btnEdit.enable();
		this.btnEditGroups.enable();
		if( this.btnEditPassword ) {
			this.btnEditPassword.enable();
		}
	},
	onBtnAddClick: function( oButton, oEvent ) {
		if ( !this.dlgUserAdd ) {
			this.dlgUserAdd = new BS.UserManager.dialog.AddUser({
				strGroups: this.strGroups
			});
			this.dlgUserAdd.on( 'ok', this.onDlgUserAddOk, this );
		}

		this.active = 'add';
		this.dlgUserAdd.setTitle( mw.message( 'bs-usermanager-titleadduser' ).plain() );
		this.dlgUserAdd.tfUserName.enable();
		this.dlgUserAdd.show();
		this.callParent( arguments );
	},
	onBtnEditClick: function( oButton, oEvent ) {
		var selectedRows = this.grdMain.getSelectionModel().getSelection();
		if ( selectedRows.length == 1 ){ //Single select
			if ( !this.dlgUserEdit ) {
				this.dlgUserEdit = new BS.UserManager.dialog.EditUser({});
				this.dlgUserEdit.on( 'ok', this.onDlgUserEditOk, this );
			}

			this.active = 'edit-basic-data';
			this.dlgUserEdit.setTitle( mw.message( 'bs-usermanager-titleeditdetails' ).plain() );
			this.dlgUserEdit.tfUserName.disable();
			this.dlgUserEdit.setData( selectedRows[0].getData() );
			this.dlgUserEdit.show();
		}

		this.callParent( arguments );
	},
	onBtnEditGroupsClick: function(oButton, oEvent){
		var selectedRows = this.grdMain.getSelectionModel().getSelection();

		if ( !this.dlgUserGroups ) {
			this.dlgUserGroups = new BS.UserManager.dialog.UserGroups({
				strGroups:this.strGroups
			});
			this.dlgUserGroups.on( 'ok', this.onDlgUserGroupsOk, this );
		}
		this.active = 'edit-groups';

		if( selectedRows.length > 1 ) { //Multiselect
			this.dlgUserGroups.setData( {groups: []} );
		}
		else { //Single select
			this.dlgUserGroups.setData( selectedRows[0].getData() );
		}

		this.dlgUserGroups.show();
	},
	onBtnEditPasswordClick: function(oButton, oEvent) {
		var selectedRows = this.grdMain.getSelectionModel().getSelection();
		if ( selectedRows.length == 0 ) {
			bs.util.alert( 'UMnoneselected', { text: mw.message( 'bs-usermanager-nouserselected' ).plain(), titleMsg: 'bs-usermanager-title-nouserselected' } );
			return;
		}

		if( selectedRows.length > 1 ) {
			bs.util.alert( 'UMmultipleselected', { text: mw.message( 'bs-usermanager-multipleuserselected' ).plain(), titleMsg: 'bs-usermanager-title-multipleuserselected' } );
			return;
		}

		if ( !this.dlgPassword ) {
			this.dlgPassword = new BS.UserManager.dialog.Password({});
			this.dlgPassword.on( 'ok', this.onDlgPasswordEditOk, this );
		}
		this.active = 'edit-password';
		this.dlgPassword.setData( selectedRows[0].getData() );
		this.dlgPassword.show();
	},
	onActionEditGroupClick: function(grid, rowIndex, colIndex) {
		var selectedUser = this.grdMain.getStore().getAt( rowIndex );
		this.grdMain.getSelectionModel().select(
			selectedUser
		);
		if ( !this.dlgUserGroups ) {
			this.dlgUserGroups = new BS.UserManager.dialog.UserGroups({});
			this.dlgUserGroups.on( 'ok', this.onDlgUserGroupsOk, this );
		}

		this.active = 'edit-groups';
		this.dlgUserGroups.setData( selectedUser.getData() );
		this.dlgUserGroups.show();
	},
	onActionEditPasswordClick:function(grid, rowIndex, colIndex) {
		var selectedUser = this.grdMain.getStore().getAt( rowIndex );
		this.grdMain.getSelectionModel().select(
			selectedUser
		);

		if ( !this.dlgPassword ) {
			this.dlgPassword = new BS.UserManager.dialog.Password({});
			this.dlgPassword.on( 'ok', this.onDlgPasswordEditOk, this );
		}
		this.active = 'edit-password';

		this.dlgPassword.setData( selectedUser.getData() );
		this.dlgPassword.show();
	},
	onActionDisableClick:function(grid, rowIndex, colIndex) {
		var selectedUser = this.grdMain.getStore().getAt( rowIndex );
		this.grdMain.getSelectionModel().select(
			selectedUser
		);
		if ( selectedUser.get( 'enabled' ) ) {;
			this.onBtnDisableClick( this.btnDisable, {} );
		} else {
			this.onBtnEnableClick( this.btnDisable, {} );
		}
	},
	onBtnDisableClick: function( oButton, oEvent ) {
		bs.util.confirm(
			'UMdisable',
			{
				text: mw.msg(
					'bs-usermanager-confirmdisableuser',
					this.grdMain.getSelectionModel().getSelection()[0].get( 'user_name' ),
					this.grdMain.getSelectionModel().getSelection().length
				),
				title: mw.message( 'bs-usermanager-titledisableuser' ).plain()
			},
			{
				ok: this.onDisableUserOk,
				cancel: function() {},
				scope: this
			}
		);
	},
	onBtnEnableClick: function( oButton, oEvent ) {
		bs.util.confirm(
			'UMenable',
			{
				text: mw.msg(
					'bs-usermanager-confirmenableuser',
					this.grdMain.getSelectionModel().getSelection()[0].get( 'user_name' ),
					this.grdMain.getSelectionModel().getSelection().length
				),
				title: mw.message( 'bs-usermanager-titleenableuser' ).plain()
			},
			{
				ok: this.onEnableUserOk,
				cancel: function() {},
				scope: this
			}
		);
	},
	onBtnRemoveClick: function( oButton, oEvent ) {
		if ( this.grdMain.getSelectionModel().getSelection().length == 0 ) {
			bs.util.alert( 'UMnoneselected', {
				text: mw.message( 'bs-usermanager-nouserselected' ).plain(),
				titleMsg: 'bs-usermanager-title-nouserselected'
			} );
			return;
		}
		bs.util.confirm(
			'UMremove',
			{
				text: mw.message(
					'bs-usermanager-confirmdeleteuser',
					this.grdMain.getSelectionModel().getSelection().length
				).text(),
				title: mw.message( 'bs-usermanager-titledeleteuser' ).plain()
			},
			{
				ok: this.onRemoveUserOk,
				cancel: function() {},
				scope: this
			}
		);
	},
	onRemoveUserOk: function() {
		var selectedRow = this.grdMain.getSelectionModel().getSelection();
		var userIDs = [];
		for (var i = 0; i < selectedRow.length; i++){
			userIDs.push( selectedRow[i].get( 'user_id' ) );
		}
		var me = this;
		bs.api.tasks.exec( 'usermanager', 'deleteUser', {
			userIDs: userIDs
		}).done( function( response ) {
			me.reloadStore();
		});
	},
	onDisableUserOk: function() {

		var selectedRow = this.grdMain.getSelectionModel().getSelection();
		for (var i = 0; i < selectedRow.length; i++){
			var me = this;
			bs.api.tasks.exec( 'usermanager', 'disableUser', {
				userID: selectedRow[i].get( 'user_id' )
			}).done( function( response ) {
				me.reloadStore();
			});
		}
	},
	onEnableUserOk: function() {
		var selectedRow = this.grdMain.getSelectionModel().getSelection();
		for (var i = 0; i < selectedRow.length; i++){
			var me = this;
			bs.api.tasks.exec( 'usermanager', 'enableUser', {
				userID: selectedRow[i].get( 'user_id' )
			}).done( function( response ) {
				me.reloadStore();
			});
		}
	},
	onDlgUserAddOk: function( data, user ) {
		var data = {
			userName: user.user_name,
			password: user.user_password,
			rePassword: user.user_repassword,
			email: user.user_email,
			realname: user.user_real_name,
			enabled: user.enabled,
			groups: user.groups
		};
		var me = this;
		var cfg = {//copy from bluespice.api.js
			failure: function( response, module, task, $dfd, cfg ) {
				var message = response.message || '';
				if ( response.errors.length > 0 ) {
					for ( var i in response.errors ) {
						if ( typeof( response.errors[i].message ) !== 'string' ) continue;
						message = message + '<br />' + response.errors[i].message;
					}
				}
				bs.util.alert( module + '-' + task + '-fail', {
						titleMsg: 'bs-extjs-title-warning',
						text: message
					}, {
						ok: function() {
							me.showDlgAgain();
					}}
				);
			}
		};

		bs.api.tasks.exec( 'usermanager', 'addUser', data, cfg ).done( function( response ) {
			me.dlgUserAdd.resetData();
			me.reloadStore();
		});
	},
	onDlgUserEditOk: function( data, user ) {
		var data = {
			userID: user.user_id,
			userName: user.user_name,
			email: user.user_email,
			realname: user.user_real_name,
			enabled: user.enabled,
			groups: user.groups
		};

		var me = this;
		var cfg = {//copy from bluespice.api.js
			failure: function( response, module, task, $dfd, cfg ) {
				var message = response.message || '';
				if ( response.errors.length > 0 ) {
					for ( var i in response.errors ) {
						if ( typeof( response.errors[i].message ) !== 'string' ) continue;
						message = message + '<br />' + response.errors[i].message;
					}
				}
				bs.util.alert( module + '-' + task + '-fail', {
						titleMsg: 'bs-extjs-title-warning',
						text: message
					}, {
						ok: function() {
							me.showDlgAgain();
					}}
				);
			}
		};
		bs.api.tasks.exec(
			'usermanager',
			'editUser',
			data,
			cfg
		).done( function( response ) {
			me.dlgUserEdit.resetData();
			me.reloadStore();
		});
	},
	onDlgPasswordEditOk: function( data, user ) {
		var data = {
			userID: user.user_id,
			password: user.user_password,
			rePassword: user.user_repassword,
		};

		var me = this;
		var passwordChangePromise;

		var currentUserId = mw.config.get( 'wgUserId' );
		if( currentUserId === parseInt( data.userID ) ) {
			bs.util.confirm('bs-usermanager-self-change-password',
				{
					text: mw.message( 'bs-usermanager-own-password-change' ).plain(),
				}, {
					ok: function ( ) {
						passwordChangePromise = me.doChangePassword( data );
						passwordChangePromise.then( function( response ) {
							var loginTitle = mw.Title.makeTitle( -1, 'Login' );
							var currentTitle = mw.Title.makeTitle(
								mw.config.get( 'wgNamespaceNumber' ),
								mw.config.get( 'wgTitle' )
							);
							window.location.href = loginTitle.getUrl( {
								returnto: currentTitle.getPrefixedText()
							} );
						} );
					},
					cancel: function() {
						me.dlgPassword.resetData();
					},
					scope: this
				}
			);
		} else {
			passwordChangePromise = me.doChangePassword( data );
			passwordChangePromise.then( function( response ) {
				me.dlgPassword.resetData();
				me.reloadStore();
			} );
		}
	},
	doChangePassword: function( data ) {
		var result = $.Deferred();

		var me = this;
		var cfg = {//copy from bluespice.api.js
			failure: function( response, module, task, $dfd, cfg ) {
				var message = response.message || '';
				if ( response.errors && response.errors.length > 0 ) {
					for ( var i in response.errors ) {
						if ( typeof( response.errors[i].message ) !== 'string' ) continue;
						message = message + '<br />' + response.errors[i].message;
					}
				}
				bs.util.alert( module + '-' + task + '-fail', {
						titleMsg: 'bs-extjs-title-warning',
						text: message
					}, {
						ok: function() {
							me.showDlgAgain();
					}}
				);
			}
		};
		bs.api.tasks.exec(
			'usermanager',
			'editPassword',
			data,
			cfg
		).done( function( response ) {
			result.resolve( response );
		});

		return result.promise();
	},
	onDlgUserGroupsOk: function( sender, data ) {
		var selectedRow = this.grdMain.getSelectionModel().getSelection();
		var userIDs = [];
		var me = this;
		for (var i = 0; i < selectedRow.length; i++){
			userIDs.push( selectedRow[i].get( 'user_id' ) );
		}
		bs.api.tasks.exec( 'usermanager', 'setUserGroups', {
			userIDs: userIDs,
			groups: data.groups
		}).done( function( response ) {
			me.reloadStore();
		});
	},
	reloadStore: function() {
		this.strMain.reload();
	},
	showDlgAgain: function() {
		if ( this.active === 'add' ) {
			this.dlgUserAdd.show();
		} else if ( this.active === 'edit-basic-data' ) {
			this.dlgUserEdit.show();
		} else if ( this.active === 'edit-groups' ) {
			this.dlgUserGroups.show();
		} else if (this.active === 'edit-password' ) {
			this.dlgPassword.show();
		}
	}
} );
