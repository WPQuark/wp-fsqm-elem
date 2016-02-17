/**
 * Callback for calculating ipicker element report
 *
 * @method     fsqm_elm_report_ipicker_cb
 * @param      {array}          element          Element settings
 * @param      {array}          e_data           Element data
 * @param      {object}         response_data    JSON response
 * @param      {object}         methods          Available methods of the calling script
 * @param      {int}            m_key            Key of element
 * @param      {jQuery Object}  table_to_update  jQuery reference of table to update
 * @param      {jQuery Object}  data_table       jQuery reference of data table
 * @param      {object}         op               Options
 */
var fsqm_elm_report_ipicker_cb = function( element, e_data, response_data, methods, m_key, table_to_update, data_table, op ) {
	for ( var k in response_data ) {
		e_data[k] += response_data[k];
	}
};

/**
 * Callback for drawing ipicker chart
 *
 * @method     fsqm_elm_report_ipicker_cb
 * @param      {array}          element          Element settings
 * @param      {array}          e_data           Element data
 * @param      {object}         methods          Available methods of the calling script
 * @param      {int}            m_key            Key of element
 * @param      {jQuery Object}  table_to_update  jQuery reference of table to update
 * @param      {jQuery Object}  data_table       jQuery reference of data table
 * @param      {object}         op               Options
 */
var fsqm_elm_report_ipicker_gcb = function( element, e_data, methods, m_key, table_to_update, data_table, op ) {
	// First update the data table
	for ( var k in e_data ) {
		data_table.find('td.' + k).html( e_data[k] );
	}

	// Now create the visualization div
	var viz_div = document.createElement('div');
	table_to_update.find('td.visualization').append(viz_div);

	// Populate charts data
	var g_data = [];
	g_data[0] = [fsqmElemJS.olabel, fsqmElemJS.clabel];
	g_data[1] = [element.settings.icon1_label, e_data.icon1];
	g_data[2] = [element.settings.icon2_label, e_data.icon2];

	// Draw it
	methods.drawPieChart( viz_div, g_data, element.title );
};

/**
 * Callback for handling freetype report for currency element
 *
 * @method     fsqm_elm_report_ipicker_cb
 * @param      {array}          element          Element settings
 * @param      {array}          e_data           Element data
 * @param      {object}         response_data    JSON response
 * @param      {object}         methods          Available methods of the calling script
 * @param      {int}            m_key            Key of element
 * @param      {jQuery Object}  table_to_update  jQuery reference of table to update
 * @param      {object}         op               Options
 */
var fsqm_elm_report_currency_cb = function( element, e_data, response_data, methods, m_key, table_to_update, op ) {
	for(var feedback in response_data) {
		var other = response_data[feedback];
		new_tr = jQuery('<tr />');
		new_tr.append('<th>' + other.value + '</th>');
		new_tr.append('<td><a class="thickbox" href="' + op.ajaxurl + '?action=ipt_fsqm_quick_preview&id=' + other.id + '">' + other.name + '</a>');
		new_tr.append('<td>' + other.email + '</td>');
		new_tr.append('<td>' + other.phone + '</td>');
		new_tr.append('<td>' + other.date + '</td>');
		table_to_update.find('> tbody').append(new_tr);
	}
};
