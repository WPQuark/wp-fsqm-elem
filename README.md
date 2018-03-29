<!---
 Copyright (C) 2018 Swashata Ghosh <swashata@wpquark.com>

 This file is part of eForm - WordPress Builder.

 eForm - WordPress Builder is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 eForm - WordPress Builder is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with eForm - WordPress Builder.  If not, see <http://www.gnu.org/licenses/>.

-->
# eForm Extended Element BoilerPlate

[![Repo on GitHub](https://img.shields.io/badge/repo-GitHub-3D76C2.svg)](https://github.com/WPQuark/wp-fsqm-elem)
[![Repo on GitLab](https://img.shields.io/badge/repo-GitLab-6C488A.svg)](https://wpquark.io/wpq-develop/wp-fsqm-elem)

This boilerplate is provided to show example code for extending eForm to add
your own elements.

Adding your own elements has 4 steps.

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [1: Hook into element definition](#1-hook-into-element-definition)
- [2: Hook into element structure](#2-hook-into-element-structure)
- [3: Prepare the callbacks](#3-prepare-the-callbacks)
- [4: Hook to JS for generating reports](#4-hook-to-js-for-generating-reports)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## 1: Hook into element definition

You need to tell eForm about custom element and define it in a way eForm understands.

```php
// Add the filter
add_filter( 'ipt_fsqm_filter_valid_elements', array( $this, 'element_base_valid' ), 10, 2 );

// Extend code
public function element_base_valid( $elements, $form_id ) {
	// Add our MCQ element
	// It is basically a picker for icons
	$elements['mcq']['elements']['ipicker'] = array(
		'title'                      => __( 'Icon Picker', 'eform_elm' ),
		'description'                => __( 'Let your user pick between icons', 'eform_elm' ),
		'm_type'                     => 'mcq',
		'type'                       => 'ipicker',
		'callback'                   => array( $this, 'ipicker_cb' ),                           // Callbacks for Admin/Data/Front classes
		'callback_report'            => array( $this, 'ipicker_report_cb' ),                    // Callback for report generator
		'callback_report_calculator' => array( $this, 'ipicker_report_cal_cb' ),                // Callback for report calculator
		'callback_data_validation'   => array( $this, 'ipicker_validation' ),                   // Callback for data validation on server side
		'callback_value'             => [ $this, 'ipicker_cb_value' ],                          // Callback for value class
	);

	// Add our freetype element
	// It is basically a currency input
	$elements['freetype']['elements']['currency'] = array(
		'title'                      => __( 'Currency Input', 'eform_elm' ),
		'description'                => __( 'Let your user enter an amount in specified currency', 'eform_elm' ),
		'm_type'                     => 'freetype',
		'type'                       => 'currency',
		'callback'                   => array( $this, 'currency_cb' ),                                             // Callbacks for Admin/Data/Front classes
		'callback_report'            => array( $this, 'currency_report_cb' ),                                      // Callback for report generator
		'callback_report_calculator' => array( $this, 'currency_report_cal_cb' ),                                  // Callback for report calculator
		'callback_data_validation'   => array( $this, 'currency_validation' ),                                     // Callback for data validation on server side
		'callback_value'             => [ $this, 'currency_cb_value' ],                                            // Callback for value class
	);

	return $elements;
}
```

## 2: Hook into element structure

Now you need to tell eForm about the element structure.

```php
// Base structure filter
add_filter( 'ipt_fsqm_form_element_structure', array( $this, 'element_base_structure' ), 10, 3 );

/**
* Filter default element structure
* And add our own structures
*
* @param      array  $default  Associative array of element structure
* @param      string $element  Element type
* @param      int    $form_id  Form ID
*
* @return     array
*/
public function element_base_structure( $default, $element, $form_id ) {
	switch ( $element ) {
		default :
			return $default;
			break;
		case 'currency' :
			$default = array(
				'type' => $element, // Required
				'm_type' => 'freetype', // Required
				'title' => 'Currency Input Title', // Default element title for frontend
				'subtitle' => '', // Default element subtitle for frontend
				'validation' => array(
					'required' => false, // Validation array
				),
				'description' => '', // Default element description for frontend
				'tooltip' => '',
				'settings'   => array(
					'vertical' => false,
					'centered' => false,
					'hidden_label' => false,
					'placeholder' => __( 'Enter Currency', 'eform_elm' ),
					'icon' => 0xf155, // To be used with fonticonpicker
					'max' => '',
					'min' => '',
					'step' => '',
				),
				'conditional' => array( // Conditional logic
					'active' => false, // True to use conditional logic, false to ignore
					'status' => false, // Initial status -> True for shown, false for hidden
					'change' => true, // Change to status -> True for shown, false for hide
					'logic' => array( // element dependent logics
						// This will get populated automatically
					),
				),
			);
			break;
		case 'ipicker' :
			$default = array(
				'm_type' => 'mcq',
				'type' => 'ipicker',
				'title' => 'IconPicker Title',
				'subtitle' => '',
				'validation' => array(
					'required' => false,
				),
				'description' => '',
				'tooltip' => '',
				'conditional' => array(
					'active' => false,
					'status' => false,
					'change' => true,
					'logic' => array(),
				),
				'settings' => array(
					'vertical' => false,
					'centered' => false,
					'hidden_label' => false,
					'icon1' => 0xe0eb,
					'icon1_label' => __( 'Heart filled with love', 'eform_elm' ),
					'icon2' => 0xe0ed,
					'icon2_label' => __( 'Broken Heart', 'eform_elm' ),
				),
			);
			break;
	}

	return $default;
}
```

## 3: Prepare the callbacks

While defining the element, we have specified the callbacks for populating
elements. Now we need to define them.

```php

// Populate stuff for admin, front and data(summary table)
public function ipicker_cb( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {
	// Admin callback
	if ( $that instanceof IPT_FSQM_Form_Elements_Admin ) {
		$this->ipicker_cb_admin( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that );

	// Frontend callback
	} elseif ( $that instanceof IPT_FSQM_Form_Elements_Front ) {
		$this->ipicker_cb_front( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that );

	// Data callback
	} elseif ( $that instanceof IPT_FSQM_Form_Elements_Data ) {
		$this->ipicker_cb_data( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that );
	} else {
		echo 'Not known instance';
	}
}

// Check if submitted data is valid
public function ipicker_validation( $element, $data, $key ) {
	// Prepare the return
	$validation_result = array(
		'data_tampering'      => false,
		'required_validation' => true,
		'errors'              => array(),
		'conditional_hidden'  => false,
		'data'                => $data,
	);
	// Do something
	return $validation_result;
}

// populate the table for reports
public function ipicker_report_cb( $visualization, $element_data, $do_data, $do_names, $do_date, $do_others, $sensitive_data, $theader, $that ) {
	// Do something for the report container
}

// calculate data for reports
public function ipicker_report_cal_cb( $element, $data, $m_key, $do_data, $do_names, $do_others, $sensitive_data, $do_date, $return, $obj ) {
	// Do something
}

// callback for the value class
public function ipicker_cb_value( $element, $submission, $type, $data, $key ) {
	$return = [];
	if ( 'label' == $data ) {
		$return['value'] = $element['settings'][ $submission['value'] . '_label' ];
	} else {
		$return['value'] = $submission['value'];
	}
	if ( 'string' === $type ) {
		return $return['value'];
	}
	return $return;
}
```

Check the example code to see how we have populated

## 4: Hook to JS for generating reports

Now we need to hook into JavaScript `enqueue` and tell eForm about what function
to use when generating reports.

```php
// Report related filter and action
add_filter( 'ipt_fsqm_report_js', array( $this, 'report_obj_filter' ) );
add_action( 'ipt_fsqm_report_enqueue', array( $this, 'report_enqueue' ) );

public function report_obj_filter( $obj ) {
	$obj['gcallbacks']['ipicker'] = 'fsqm_elm_report_ipicker_gcb';
	$obj['callbacks']['ipicker'] = 'fsqm_elm_report_ipicker_cb';
	$obj['callbacks']['currency'] = 'fsqm_elm_report_currency_cb';
	return $obj;
}

public function report_enqueue() {
	wp_enqueue_script( 'fsqm-elem-js', plugins_url( '/js/fsqm-elem-report.js', self::$absfile ), array('jquery'), self::$version );
	wp_localize_script( 'fsqm-elem-js', 'fsqmElemJS', array(
		'olabel' => __( 'Options', 'eform_elm' ),
		'clabel' => __( 'Count', 'eform_elm' ),
	) );
}
```

The JavaScript needs to explicity generate `window.fsqm_elm_report_ipicker_cb`
and `window.fsqm_elm_report_ipicker_gcb` for generating the table data and chart
data. The functions may look like this. Check the `fsqm-elem-report.js` file to
learn more.

```js
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
var fsqm_elm_report_ipicker_cb = function( element, e_data, response_data, m_key, table_to_update, data_table, op ) {
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
var fsqm_elm_report_ipicker_gcb = function( element, e_data, m_key, table_to_update, data_table, op, show_title, show_legend ) {
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
	this.drawPieChart( viz_div, g_data, element.title, {}, show_title, show_legend );
};
```

For any additional queries, kindly visit our [support forum](https://wpquark.com/kb/support).

**Happy Coding**

