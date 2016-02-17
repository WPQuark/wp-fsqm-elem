<?php

/**
 * This class is responsible for extending FSQM Pro
 * With two more elements
 */
class IPT_FSQM_Ext_Elm {
	public static $absfile;
	public static $absdir;
	public static $version;

	public function __construct( $absfile, $absdir, $version ) {
		// Set the static variables
		self::$absfile = $absfile;
		self::$absdir = $absdir;
		self::$version = $version;

		// Add to the filter to add our new elements
		// Base definition filter
		add_filter( 'ipt_fsqm_filter_valid_elements', array( $this, 'element_base_valid' ), 10, 2 );

		// Base structure filter
		add_filter( 'ipt_fsqm_form_element_structure', array( $this, 'element_base_structure' ), 10, 3 );

		// Base submission filter
		add_filter( 'ipt_fsqm_filter_form_data_structure', array( $this, 'element_base_data_structure' ), 10, 3 );
	}

	/*==========================================================================
	 * System required callbacks
	 * and filter/hook callbacks
	 *========================================================================*/


	public function element_base_valid( $elements, $form_id ) {
		// Add our MCQ element
		// It is basically a picker for icons
		$elements['mcq']['elements']['ipicker'] = array(
			'title' => __( 'Icon Picker', 'fsqm_elm' ),
			'description' => __( 'Let your user pick between icons', 'fsqm_elm' ),
			'm_type' => 'mcq',
			'type' => 'ipicker',
			'callback' => array( $this, 'ipicker_cb' ), // Callbacks for Admin/Data/Front classes
			'callback_report' => array( $this, 'ipicker_report_cb' ), // Callback for report generator
			'callback_report_calculator' => array( $this, 'ipicker_report_cal_cb' ), // Callback for report calculator
			'callback_data_validation' => array( $this, 'ipicker_validation' ), // Callback for data validation on server side
		);

		// Add our freetype element
		// It is basically a currency input
		$elements['freetype']['elements']['currency'] = array(
			'title' => __( 'Currency Input', 'fsqm_elm' ),
			'description' => __( 'Let your user enter an amount in specified currency', 'fsqm_elm' ),
			'm_type' => 'freetype',
			'type' => 'currency',
			'callback' => array( $this, 'currency_cb' ), // Callbacks for Admin/Data/Front classes
			'callback_report' => array( $this, 'currency_report_cb' ), // Callback for report generator
			'callback_report_calculator' => array( $this, 'currency_report_cal_cb' ), // Callback for report calculator
			'callback_data_validation' => array( $this, 'currency_validation' ), // Callback for data validation on server side
		);

		return $elements;
	}

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
					'title' => '', // Default element title for frontend
					'validation' => array(
						'required' => false, // Validation array
					),
					'subtitle' => '', // Default element subtitle for frontend
					'description' => '', // Default element description for frontend
					'settings'   => array(
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
					'title' => '',
					'validation' => array(
						'required' => false,
					),
					'subtitle' => '',
					'description' => '',
					'conditional' => array(
						'active' => false,
						'status' => false,
						'change' => true,
						'logic' => array(),
					),
					'settings' => array(
						'icon1' => 0xe0d1,
						'icon1_label' => __( 'Heart filled with love', 'fsqm_elm' ),
						'icon2' => 0xe0d3,
						'icon_2_label' => __( 'Broken Heart', 'fsqm_elm' ),
					),
				);
				break;
		}

		return $default;
	}

	/**
	 * Add to default submission structure to incorporate our elements
	 *
	 * @param      array   $default  Associative array of our element data structure
	 * @param      string  $element  Element type
	 * @param      int     $form_id  Form ID
	 *
	 * @return     array
	 */
	public function element_base_data_structure( $default, $element, $form_id ) {
		switch ( $element ) {
			case 'currency' :
				$default['m_type'] = 'freetype';
				$default['value'] = '';
				break;
			case 'ipicker' :
				$default['m_type'] = 'mcq';
				$default['value'] = '';
				break;
			default :
				return $default;
				break;
		}

		return $default;
	}


	/*==========================================================================
	 * Methods for actually implementing the ipicker element
	 *========================================================================*/


	public function ipicker_cb( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {

	}

	public function ipicker_report_cb( $do_data ) {

	}

	public function ipicker_report_cal_cb( $element, $data, $m_key, $do_data, $return ) {

	}

	public function ipicker_validation( $element, $data, $key ) {

	}

	public function ipicker_cb_admin( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {

	}

	public function ipicker_cb_front( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {

	}

	public function ipicker_cb_data( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {

	}


	/*==========================================================================
	 * Methods for actually implementing the currency element
	 *========================================================================*/


	public function currency_cb( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {

	}

	public function currency_report_cb( $do_data ) {

	}

	public function currency_report_cal_cb( $element, $data, $m_key, $do_data, $return ) {

	}

	public function currency_validation( $element, $data, $key ) {

	}

	public function currency_cb_admin( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {

	}

	public function currency_cb_front( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {

	}

	public function currency_cb_data( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {

	}
}
