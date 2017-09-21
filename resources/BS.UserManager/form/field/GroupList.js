Ext.define( 'BS.UserManager.form.field.GroupList', {
	extend: 'BS.form.field.ItemList',
	fieldLabel:  mw.message('bs-usermanager-headergroups').plain(),
	apiStore: 'bs-usermanager-group-store',
	apiFields: [
		{ name: 'type', defaultValue: 'group' },
		'group_name',
		'displayname'
	],
	inputDisplayField: 'displayname',
	listDisplayField: 'displayname',
	idProperty: 'group_name',

	/**
	 * This is a very dirty workaround. 'BS.form.field.ItemList' can handle a
	 * display name and a cononical name of a group. Unfortunately
	 * 'bs-adminuser-store' returns only an array of canonical group names
	 * Therefore we build the "display name" from the "canonical name", wich
	 * is bad but the only way without changing the API.
	 * @param array data
	 * @returns {undefined}
	 */
	setValue: function( data ) {
		data = data || [];
		var newData = [];
		for( var i = 0; i < data.length; i++ ) {
			var item = {
				'group_name': data[i],
				'displayname': data[i]
			};
			newData.push(item);
		}

		this.callParent( [ newData ] );
	},

	getValue: function() {
		var value = this.callParent( arguments );
		var newValue = [];
		for ( var i = 0; i < value.length; i++ ) {
			newValue.push( value[i].group_name );
		}

		return newValue;
	}
});