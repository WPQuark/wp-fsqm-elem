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
						'icon1' => 0xe0eb,
						'icon1_label' => __( 'Heart filled with love', 'fsqm_elm' ),
						'icon2' => 0xe0ed,
						'icon2_label' => __( 'Broken Heart', 'fsqm_elm' ),
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

	public function ipicker_report_cb( $do_data ) {

	}

	public function ipicker_report_cal_cb( $element, $data, $m_key, $do_data, $return ) {

	}

	public function ipicker_validation( $element, $data, $key ) {

	}

	/**
	 * Administrative callback for populating setting fields
	 *
	 * @param      array   $element_definition    Definition of element
	 * @param      int     $key                   Element key
	 * @param      array   $element_data          Element settings data
	 * @param      array   $element_structure     Element default structure
	 * @param      string  $name_prefix           Prefix of HTML form element name attribute
	 * @param      array   $submission_data       Submission data from user
	 * @param      array   $submission_structure  Default submission structure
	 * @param      object  $that                  Reference to the calling object
	 */
	public function ipicker_cb_admin( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {

		// 'settings' => array(
		// 	'icon1' => 0xe0d1,
		// 	'icon1_label' => __( 'Heart filled with love', 'fsqm_elm' ),
		// 	'icon2' => 0xe0d3,
		// 	'icon2_label' => __( 'Broken Heart', 'fsqm_elm' ),
		// ),
		$that->ui->textarea_linked_wp_editor( $name_prefix . '[description]', $element_data['description'], '' );
		?>
	<table class="form-table">
		<thead>
			<tr>
				<th colspan="3" style="text-align: center;"><h3><?php echo $element_definition['title']; ?></h3></th>
			</tr>
			<tr>
				<td colspan="3" style="text-align: center;" ><span class="description"><?php echo $element_definition['description']; ?></span></td>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td colspan="3"><?php $that->ui->text( $name_prefix . '[title]', $element_data['title'], __( 'Enter Primary Label', 'ipt_fsqm' ), 'large' ); ?></td>
			</tr>
			<tr>
				<td colspan="3"><?php $that->ui->text( $name_prefix . '[subtitle]', $element_data['subtitle'], __( 'Description Text (Optional)', 'ipt_fsqm' ), 'large' ); ?></td>
			</tr>
			<tr>
				<th><?php $that->ui->generate_label( $name_prefix . '[settings][icon1]', __( 'First Icon', 'fsqm_elm' ) ); ?></th>
				<td>
					<?php $that->ui->icon_selector( $name_prefix . '[settings][icon1]', $element_data['settings']['icon1'], __( 'Do not use any icon', 'fsqm_elm' ) ); ?>
				</td>
				<td><?php $that->ui->help( __( 'Select the icon', 'fsqm_elm' ) ); ?></td>
			</tr>
			<tr>
				<th><?php $that->ui->generate_label( $name_prefix . '[settings][icon1_label]', __( 'First Icon Label', 'fsqm_elm' ) ); ?></th>
				<td>
					<?php $that->ui->text( $name_prefix . '[settings][icon1_label]', $element_data['settings']['icon1_label'], __( 'Enter the label', 'fsqm_elm' ), 'large' ); ?>
				</td>
				<td><?php $that->ui->help( __( 'Enter the label for this icon.', 'fsqm_elm' ) ) ?></td>
			</tr>
			<tr>
				<th><?php $that->ui->generate_label( $name_prefix . '[settings][icon2]', __( 'Second Icon', 'fsqm_elm' ) ); ?></th>
				<td>
					<?php $that->ui->icon_selector( $name_prefix . '[settings][icon2]', $element_data['settings']['icon2'], __( 'Do not use any icon', 'fsqm_elm' ) ); ?>
				</td>
				<td><?php $that->ui->help( __( 'Select the icon', 'fsqm_elm' ) ); ?></td>
			</tr>
			<tr>
				<th><?php $that->ui->generate_label( $name_prefix . '[settings][icon2_label]', __( 'Second Icon Label', 'fsqm_elm' ) ); ?></th>
				<td>
					<?php $that->ui->text( $name_prefix . '[settings][icon2_label]', $element_data['settings']['icon2_label'], __( 'Enter the label', 'fsqm_elm' ), 'large' ); ?>
				</td>
				<td><?php $that->ui->help( __( 'Enter the label for this icon.', 'fsqm_elm' ) ) ?></td>
			</tr>
		</tbody>
	</table>
		<?php
		$that->build_validation( $name_prefix, $element_structure['validation'], $element_data['validation'] );
		$that->build_conditional( $name_prefix, $element_data['conditional'] );
	}

	/**
	 * Frontend callback for populating form element
	 * This is just an example
	 * We could actually do far better
	 *
	 * @param      array   $element_definition    Definition of element
	 * @param      int     $key                   Element key
	 * @param      array   $element_data          Element settings data
	 * @param      array   $element_structure     Element default structure
	 * @param      string  $name_prefix           Prefix of HTML form element name attribute
	 * @param      array   $submission_data       Submission data from user
	 * @param      array   $submission_structure  Default submission structure
	 * @param      object  $that                  Reference to the calling object
	 */
	public function ipicker_cb_front( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {
		$items = array();
		$items[] = array(
			'label' => '<i class="ipt-icomoon" data-ipt-icomoon="&#x' . dechex( $element_data['settings']['icon1'] ) . '"></i> ' . $element_data['settings']['icon1_label'],
			'value' => 'icon1',
		);
		$items[] = array(
			'label' => '<i class="ipt-icomoon" data-ipt-icomoon="&#x' . dechex( $element_data['settings']['icon2'] ) . '"></i> ' . $element_data['settings']['icon2_label'],
			'value' => 'icon2',
		);
		$param = array( $name_prefix . '[value]', $items, $submission_data['value'], $element_data['validation'], 2 );
		$id = 'ipt_fsqm_form_' . $that->form_id . '_' . $element_structure['m_type'] . '_' . $key;
		$that->ui->column_head( $id, 'full', true, 'ipt_fsqm_container_radio' );
		$that->ui->question_container( $name_prefix, $element_data['title'], $element_data['subtitle'], array( array( $that->ui, 'radios' ), $param ), $element_data['validation']['required'], false, false, $element_data['description'] );
		$that->ui->column_tail();
	}

	public function ipicker_cb_data( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {

	}


	/*==========================================================================
	 * Methods for actually implementing the currency element
	 *========================================================================*/


	public function currency_cb( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {
		// Admin callback
		if ( $that instanceof IPT_FSQM_Form_Elements_Admin ) {
			$this->currency_cb_admin( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that );

		// Frontend callback
		} elseif ( $that instanceof IPT_FSQM_Form_Elements_Front ) {
			$this->currency_cb_front( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that );

		// Data callback
		} elseif ( $that instanceof IPT_FSQM_Form_Elements_Data ) {
			$this->currency_cb_data( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that );
		} else {
			echo 'Not known instance';
		}
	}

	public function currency_report_cb( $do_data ) {

	}

	public function currency_report_cal_cb( $element, $data, $m_key, $do_data, $return ) {

	}

	public function currency_validation( $element, $data, $key ) {

	}

	/**
	 * Administrative callback for populating setting fields
	 *
	 * @param      array   $element_definition    Definition of element
	 * @param      int     $key                   Element key
	 * @param      array   $element_data          Element settings data
	 * @param      array   $element_structure     Element default structure
	 * @param      string  $name_prefix           Prefix of HTML form element name attribute
	 * @param      array   $submission_data       Submission data from user
	 * @param      array   $submission_structure  Default submission structure
	 * @param      object  $that                  Reference to the calling object
	 */
	public function currency_cb_admin( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {
		// 'settings'   => array(
		// 	'icon' => 0xf155, // To be used with fonticonpicker
		// 	'max' => '',
		// 	'min' => '',
		// 	'step' => '',
		// ),
		//
		$that->ui->textarea_linked_wp_editor( $name_prefix . '[description]', $element_data['description'], '' );
		?>
	<table class="form-table">
		<thead>
			<tr>
				<th colspan="3" style="text-align: center;"><h3><?php echo $element_definition['title']; ?></h3></th>
			</tr>
			<tr>
				<td colspan="3" style="text-align: center;" ><span class="description"><?php echo $element_definition['description']; ?></span></td>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td colspan="3"><?php $that->ui->text( $name_prefix . '[title]', $element_data['title'], __( 'Enter Primary Label', 'ipt_fsqm' ), 'large' ); ?></td>
			</tr>
			<tr>
				<td colspan="3"><?php $that->ui->text( $name_prefix . '[subtitle]', $element_data['subtitle'], __( 'Description Text (Optional)', 'ipt_fsqm' ), 'large' ); ?></td>
			</tr>
			<tr>
				<th><?php $that->ui->generate_label( $name_prefix . '[settings][icon]', __( 'Icon', 'fsqm_elm' ) ); ?></th>
				<td>
					<?php $that->ui->icon_selector( $name_prefix . '[settings][icon]', $element_data['settings']['icon'], __( 'Do not use any icon', 'fsqm_elm' ) ); ?>
				</td>
				<td><?php $that->ui->help( __( 'Select the icon', 'fsqm_elm' ) ); ?></td>
			</tr>
			<tr>
				<th><?php $that->ui->generate_label( $name_prefix . '[settings][min]', __( 'Minumum Input', 'fsqm_elm' ) ); ?></th>
				<td>
					<?php $that->ui->spinner( $name_prefix . '[settings][min]', $element_data['settings']['min'], __( 'Enter the label', 'fsqm_elm' ), 'large' ); ?>
				</td>
				<td><?php $that->ui->help( __( 'Enter minimum input.', 'fsqm_elm' ) ) ?></td>
			</tr>
			<tr>
				<th><?php $that->ui->generate_label( $name_prefix . '[settings][max]', __( 'Maximum Input', 'fsqm_elm' ) ); ?></th>
				<td>
					<?php $that->ui->spinner( $name_prefix . '[settings][max]', $element_data['settings']['max'], __( 'Enter the label', 'fsqm_elm' ), 'large' ); ?>
				</td>
				<td><?php $that->ui->help( __( 'Enter Maximum input.', 'fsqm_elm' ) ) ?></td>
			</tr>
			<tr>
				<th><?php $that->ui->generate_label( $name_prefix . '[settings][step]', __( 'Input Step', 'fsqm_elm' ) ); ?></th>
				<td>
					<?php $that->ui->spinner( $name_prefix . '[settings][step]', $element_data['settings']['step'], __( 'Enter the label', 'fsqm_elm' ), 'large' ); ?>
				</td>
				<td><?php $that->ui->help( __( 'Enter input step.', 'fsqm_elm' ) ) ?></td>
			</tr>
		</tbody>
	</table>
		<?php
		$that->build_validation( $name_prefix, $element_structure['validation'], $element_data['validation'] );
		$that->build_conditional( $name_prefix, $element_data['conditional'] );
	}

	/**
	 * Frontend callback for populating form element
	 * This is just an example
	 * We could actually do far better
	 *
	 * @param      array   $element_definition    Definition of element
	 * @param      int     $key                   Element key
	 * @param      array   $element_data          Element settings data
	 * @param      array   $element_structure     Element default structure
	 * @param      string  $name_prefix           Prefix of HTML form element name attribute
	 * @param      array   $submission_data       Submission data from user
	 * @param      array   $submission_structure  Default submission structure
	 * @param      object  $that                  Reference to the calling object
	 */
	public function currency_cb_front( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {
		$params = array(
			$name_prefix . '[value]', $submission_data['value'], '', $element_data['settings']['min'], $element_data['settings']['max'], $element_data['settings']['step'], $element_data['validation']['required']
		);
		$id = 'ipt_fsqm_form_' . $that->form_id . '_' . $element_structure['m_type'] . '_' . $key;
		$that->ui->column_head( $id, 'full', true, 'ipt_fsqm_container_radio' );
		$that->ui->question_container( $name_prefix, '<i class="ipt-icomoon" data-ipt-icomoon="&#x' . dechex( $element_data['settings']['icon'] ) . '"></i> ' . $element_data['title'], $element_data['subtitle'], array( array( $that->ui, 'spinner' ), $params ), $element_data['validation']['required'], false, false, $element_data['description'] );
		$that->ui->column_tail();
	}

	public function currency_cb_data( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {

	}
}
