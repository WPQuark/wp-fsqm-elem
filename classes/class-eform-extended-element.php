<?php

/**
 * This class is responsible for extending FSQM Pro
 * With two more elements
 */
class EForm_Extended_Element {
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

		// Report related filter and action
		add_filter( 'ipt_fsqm_report_js', array( $this, 'report_obj_filter' ) );
		add_action( 'ipt_fsqm_report_enqueue', array( $this, 'report_enqueue' ) );
	}

	/*==========================================================================
	 * System required callbacks
	 * and filter/hook callbacks
	 *========================================================================*/

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
				$default['type'] = $element;
				$default['value'] = '';
				break;
			case 'ipicker' :
				$default['m_type'] = 'mcq';
				$default['type'] = $element;
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

	/**
	 * Callback for report generation table
	 *
	 * @param      bool    $do_data       True if sensitive data can be printed
	 * @param      array   $element_data  Associative array of element settings
	 */
	public function ipicker_report_cb( $visualization, $element_data, $do_data, $do_names, $do_date, $do_others, $sensitive_data, $theader, $that ) {

		$ui = IPT_Plugin_UIF_Front::instance();
		$data = array(
			'icon1' => 0,
			'icon2' => 0,
		);
		?>
<table class="ipt_fsqm_preview table_to_update">
	<thead>
		<tr>
			<th style="width: 50%"><?php echo $visualization; ?></th>
			<th style="width: 50%"><?php _e( 'Data', 'ipt_cs' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td style="width: 50%" class="visualization"><!-- Pie --></td>
			<td style="width: 50%" class="data">
				<table class="ipt_fsqm_preview">
					<thead>
						<tr>
							<th style="width: 80%" colspan="2"><?php _e( 'Option', 'ipt_cs' ); ?></th>
							<th style="width: 20%"><?php _e( 'Count', 'ipt_cs' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th style="width: 80%" colspan="2"><?php _e( 'Option', 'ipt_cs' ); ?></th>
							<th style="width: 20%"><?php _e( 'Count', 'ipt_cs' ); ?></th>
						</tr>
					</tfoot>
					<tbody>
						<tr>
							<td><?php echo '<img src="' . $ui->get_image_for_icon( $element_data['settings']['icon1'] ) . '" height="16" width="16" /> '; ?></td>
							<th><?php echo $element_data['settings']['icon1_label']; ?></th>
							<td class="icon1">0</td>
						</tr>
						<tr>
							<td><?php echo '<img src="' . $ui->get_image_for_icon( $element_data['settings']['icon2'] ) . '" height="16" width="16" /> '; ?></td>
							<th><?php echo $element_data['settings']['icon2_label']; ?></th>
							<td class="icon2">0</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>
		<?php
		return $data;
	}

	/**
	 * Report calculator for ipicker
	 *
	 * @param      array   $element  Element Settings
	 * @param      array   $data     Submission data
	 * @param      int     $m_key    Element key
	 * @param      bool    $do_data  True if sensitive data can be printed
	 * @param      array   $return   Return data
	 * @param      obj     $obj      Reference to IPT_FSQM_Form_Elements_Data object
	 *
	 * @return     array
	 */
	public function ipicker_report_cal_cb( $element, $data, $m_key, $do_data, $do_names, $do_others, $sensitive_data, $do_date, $return, $obj ) {
		if ( ! is_array( $return ) || empty( $return ) ) {
			$return = array(
				'icon1' => 0,
				'icon2' => 0,
			);
		}

		if ( $data['value'] == 'icon1' ) {
			$return['icon1']++;
		} elseif ( $data['value'] == 'icon2' ) {
			$return['icon2']++;
		}

		return $return;
	}

	/**
	 * Validation callback for server side processing
	 *
	 * @param      array   $element  Element Settings
	 * @param      array   $data     Submission data
	 * @param      int     $key      Element key
	 *
	 * @return     array
	 */
	public function ipicker_validation( $element, $data, $key ) {
		// Prepare the return
		$validation_result = array(
			'data_tampering'      => false,
			'required_validation' => true,
			'errors'              => array(),
			'conditional_hidden'  => false,
			'data'                => $data,
		);

		// Check for data tampering
		if ( $element['type'] !== $data['type'] || $element['m_type'] !== $data['m_type'] ) {
			$validation_result['data_tampering'] = true;
		}

		// Check for required validation
		if ( empty( $data['value'] ) && $element['validation']['required'] == true ) {
			$validation_result['required_validation'] = false;
		}

		return $validation_result;
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
		// 	'icon1_label' => __( 'Heart filled with love', 'eform_elm' ),
		// 	'icon2' => 0xe0d3,
		// 	'icon2_label' => __( 'Broken Heart', 'eform_elm' ),
		// ),
		$tab_names = $that->ui->generate_id_from_name( $name_prefix ) . '_settings_tab_';
?>
	<div class="ipt_uif_tabs">
		<ul>
			<li><a href="#<?php echo $tab_names; ?>_elm"><?php _e( 'Appearance', 'eform_elm' ); ?></a></li>
			<li><a href="#<?php echo $tab_names; ?>_ifs"><?php _e( 'Interface', 'eform_elm' ); ?></a></li>
			<li><a href="#<?php echo $tab_names; ?>_validation"><?php _e( 'Validation', 'eform_elm' ); ?></a></li>
			<li><a href="#<?php echo $tab_names; ?>_logic"><?php _e( 'Logic', 'eform_elm' ); ?></a></li>
		</ul>
		<div id="<?php echo $tab_names; ?>_elm">
			<table class="form-table">
				<tbody>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[title]', __( 'Title', 'eform_elm' ) ); ?></th>
						<td><?php $that->ui->text( $name_prefix . '[title]', $element_data['title'], __( 'Enter Primary Label', 'eform_elm' ), 'large' ); ?></td>
						<td></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[subtitle]', __( 'Subtitle', 'eform_elm' ) ); ?></th>
						<td><?php $that->ui->text( $name_prefix . '[subtitle]', $element_data['subtitle'], __( 'Description Text (Optional)', 'eform_elm' ), 'large' ); ?></td>
						<td></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][vertical]', __( 'Label Alignment', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->toggle( $name_prefix . '[settings][vertical]', __( 'Vertical', 'eform_elm' ), __( 'Horizontal', 'eform_elm' ), $element_data['settings']['vertical'], '1' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'The alignment of the label(question) and options. Making Horizontal will show the label on left, whereas making vertical will show it on top.', 'eform_elm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][centered]', __( 'Center Content', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->toggle( $name_prefix . '[settings][centered]', __( 'Yes', 'eform_elm' ), __( 'No', 'eform_elm' ), $element_data['settings']['centered'], '1' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'If enabled, then labels and elements will be centered. This will force vertical the content.', 'eform_elm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][hidden_label]', __( 'Hide Label', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->toggle( $name_prefix . '[settings][hidden_label]', __( 'Yes', 'eform_elm' ), __( 'No', 'eform_elm' ), $element_data['settings']['hidden_label'], '1' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'If enabled, then label along with subtitle and description would be hidden on the form. It would be visible only on the summary table and on emails. When using this, place a meaningful text in the placeholder.', 'eform_elm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[tooltip]', __( 'Tooltip', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->textarea( $name_prefix . '[tooltip]', $element_data['tooltip'], __( 'HTML Enabled', 'eform_elm' ) ); ?>
						</td>
						<td><?php $that->ui->help( __( 'If you want to show tooltip, then please enter it here. You can write custom HTML too. Leave empty to disable.', 'eform_elm' ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div id="<?php echo $tab_names; ?>_ifs">
			<table class="form-table">
				<tbody>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][icon1]', __( 'First Icon', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->icon_selector( $name_prefix . '[settings][icon1]', $element_data['settings']['icon1'], __( 'Do not use any icon', 'eform_elm' ) ); ?>
						</td>
						<td><?php $that->ui->help( __( 'Select the icon', 'eform_elm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][icon1_label]', __( 'First Icon Label', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->text( $name_prefix . '[settings][icon1_label]', $element_data['settings']['icon1_label'], __( 'Enter the label', 'eform_elm' ), 'large' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'Enter the label for this icon.', 'eform_elm' ) ) ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][icon2]', __( 'Second Icon', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->icon_selector( $name_prefix . '[settings][icon2]', $element_data['settings']['icon2'], __( 'Do not use any icon', 'eform_elm' ) ); ?>
						</td>
						<td><?php $that->ui->help( __( 'Select the icon', 'eform_elm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][icon2_label]', __( 'Second Icon Label', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->text( $name_prefix . '[settings][icon2_label]', $element_data['settings']['icon2_label'], __( 'Enter the label', 'eform_elm' ), 'large' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'Enter the label for this icon.', 'eform_elm' ) ) ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div id="<?php echo $tab_names; ?>_validation">
			<?php $that->build_validation( $name_prefix, $element_structure['validation'], $element_data['validation'] ); ?>
		</div>
		<div id="<?php echo $tab_names; ?>_logic">
			<?php $that->build_conditional( $name_prefix, $element_data['conditional'] ); ?>
		</div>
	</div>
	<?php
		$that->ui->textarea_linked_wp_editor( $name_prefix . '[description]', $element_data['description'], '' );
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
		$id = 'ipt_fsqm_form_' . $that->form_id . '_' . $element_structure['m_type'] . '_' . $key . '_wrap';
		$that->ui->column_head( $id, 'full', true, 'ipt_fsqm_container_ipicker', $element_data['tooltip'] );
		$that->ui->question_container( $name_prefix, $element_data['title'], $element_data['subtitle'], array( array( $that->ui, 'radios' ), $param ), $element_data['validation']['required'], false, $element_data['settings']['vertical'], $element_data['description'], [], [], $element_data['settings']['hidden_label'], $element_data['settings']['centered'] );
		$that->ui->column_tail();
	}

	public function ipicker_cb_data( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {
		$ui = IPT_Plugin_UIF_Front::instance();
		$checked = '<img src="' . $ui->get_image_for_icon( 0xe190 ) . '" height="16" width="16" />';
		$unchecked = '<img src="' . $ui->get_image_for_icon( 0xe191 ) . '" height="16" width="16" />';
		?>
		<th style="<?php echo $that->email_styling['th']; ?>" colspan="2" rowspan="2" scope="row">
			<?php echo $element_data['title']; ?><br /><span class="description" style="<?php echo $that->email_styling['description']; ?>"><?php echo $element_data['subtitle']; ?></span>
			<?php if ( $element_data['description'] !== '' ) : ?>
			<div class="ipt_uif_richtext">
				<?php echo apply_filters( 'ipt_uif_richtext', $element_data['description'] ); ?>
			</div>
			<?php endif; ?>
		</th>
		<td style="<?php echo $that->email_styling['icons']; ?>" class="icons">
		<?php if ( $submission_data['value'] == 'icon1' ) {
			echo $checked;
		} else {
			echo $unchecked;
		} ?>
		</td>
		<td style="<?php echo $that->email_styling['td']; ?>" colspan="2">
			<?php echo '<img src="' . $ui->get_image_for_icon( $element_data['settings']['icon1'] ) . '" height="16" width="16" /> ' . $element_data['settings']['icon1_label']; ?>
		</td>
	</tr>
	<tr>
		<td style="<?php echo $that->email_styling['icons']; ?>" class="icons">
		<?php if ( $submission_data['value'] == 'icon2' ) {
			echo $checked;
		} else {
			echo $unchecked;
		} ?>
		</td>
		<td style="<?php echo $that->email_styling['td']; ?>" colspan="2">
			<?php echo '<img src="' . $ui->get_image_for_icon( $element_data['settings']['icon2'] ) . '" height="16" width="16" /> ' . $element_data['settings']['icon2_label']; ?>
		</td>
		<?php
	}

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

	public function currency_report_cb( $element_data, $do_names, $do_date, $sensitive_data, $theader, $that ) {
		$data = array();

		$pinfo_titles = array(
			'name' => __( 'Name', 'eform_elm' ),
			'email' => __( 'Email', 'eform_elm' ),
			'phone' => __( 'Phone', 'eform_elm' ),
		);

		foreach ( $that->pinfo as $pinfo ) {
			if ( in_array( $pinfo['type'], array_keys( $pinfo_titles ) ) ) {
				$pinfo_titles[ $pinfo['type'] ] = $pinfo['title'];
			}
		}

		if ( ! $sensitive_data ) {
			unset( $pinfo_titles['email'] );
			unset( $pinfo_titles['phone'] );
		}

		if ( ! $do_names ) {
			unset( $pinfo_titles['name'] );
		}

		// NOTE: We increase pinfo count if do_date is set to true
		$pinfo_count = count( $pinfo_titles );
		if ( $do_date ) {
			$pinfo_count++;
		}
		?>
<table class="ipt_fsqm_preview table_to_update">
	<?php if ( $theader ) : ?>
	<thead>
		<tr>
			<th style="width: 40%;"><?php _e( 'Feedback', 'eform_elm' ); ?></th>
			<?php foreach ( $pinfo_titles as $p_val ) : ?>
			<th><?php echo $p_val; ?></th>
			<?php endforeach; ?>
			<?php if ( $do_date ) : ?>
			<th style="width: 10%"><?php _e( 'Date', 'eform_elm' ); ?></th>
			<?php endif; ?>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th style="width: 40%;"><?php _e( 'Feedback', 'eform_elm' ); ?></th>
			<?php foreach ( $pinfo_titles as $p_val ) : ?>
			<th><?php echo $p_val; ?></th>
			<?php endforeach; ?>
			<?php if ( $do_date ) : ?>
			<th style="width: 10%"><?php _e( 'Date', 'eform_elm' ); ?></th>
			<?php endif; ?>
		</tr>
	</tfoot>
	<?php endif; ?>
	<tbody>
		<tr class="empty">
			<td colspan="<?php echo ( $pinfo_count + 1 ); ?>"><?php _e( 'No data yet!', 'eform_elm' ); ?></td>
		</tr>
	</tbody>
</table>
		<?php
		return $data;
	}

	/**
	 * Report calculator for ipicker
	 *
	 * @param      array   $element  Element Settings
	 * @param      array   $data     Submission data
	 * @param      int     $m_key    Element key
	 * @param      bool    $do_data  True if sensitive data can be printed
	 * @param      array   $return   Return data
	 * @param      obj     $obj      Reference to IPT_FSQM_Form_Elements_Data object
	 *
	 * @return     array
	 */
	public function currency_report_cal_cb( $element, $data, $m_key, $do_data, $do_names, $sensitive_data, $do_date, $return, $obj ) {
		if ( empty( $data['value'] ) ) {
			return $return;
		}
		$report_data = [
			'value' => $data['value'],
			// 'name'  => $obj->data->f_name . ' ' . $obj->data->l_name,
		];
		if ( $do_date ) {
			$report_data['date'] = date_i18n( get_option( 'date_format' ) . __(' \a\t ', 'eform_elm') . get_option( 'time_format' ), strtotime( $obj->data->date ) );
		}
		if ( $do_names ) {
			$report_data['name'] = $obj->data->f_name . ' ' . $obj->data->l_name;
		}

		if ( $sensitive_data ) {
			$report_data['email'] = $obj->data->email == '' ? __( 'anonymous', 'eform_elm' ) : '<a href="mailto:' . $obj->data->email . '">' . $obj->data->email . '</a>';
			$report_data['phone'] = $obj->data->phone;
			$report_data['id']    = $obj->data_id;
		}

		$return[] = $report_data;

		return $return;
	}

	/**
	 * Validation callback for server side processing
	 *
	 * @param      array   $element  Element Settings
	 * @param      array   $data     Submission data
	 * @param      int     $key      Element key
	 *
	 * @return     array
	 */
	public function currency_validation( $element, $data, $key ) {
		// Prepare the return
		$validation_result = array(
			'data_tampering'      => false,
			'required_validation' => true,
			'errors'              => array(),
			'conditional_hidden'  => false,
			'data'                => $data,
		);

		// Check for data tampering
		if ( $element['type'] !== $data['type'] || $element['m_type'] !== $data['m_type'] ) {
			$validation_result['data_tampering'] = true;
		}

		// Check for required validation
		if ( empty( $data['value'] ) && $element['validation']['required'] == true ) {
			$validation_result['required_validation'] = false;
		}

		return $validation_result;
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
		$tab_names = $that->ui->generate_id_from_name( $name_prefix ) . '_settings_tab_';
		?>
	<div class="ipt_uif_tabs">
		<ul>
			<li><a href="#<?php echo $tab_names; ?>_elm"><?php _e( 'Appearance', 'eform_elm' ); ?></a></li>
			<li><a href="#<?php echo $tab_names; ?>_ifs"><?php _e( 'Interface', 'eform_elm' ); ?></a></li>
			<li><a href="#<?php echo $tab_names; ?>_validation"><?php _e( 'Validation', 'eform_elm' ); ?></a></li>
			<li><a href="#<?php echo $tab_names; ?>_logic"><?php _e( 'Logic', 'eform_elm' ); ?></a></li>
		</ul>
		<div id="<?php echo $tab_names; ?>_elm">
			<table class="form-table">
				<tbody>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[title]', __( 'Title', 'eform_elm' ) ); ?></th>
						<td><?php $that->ui->text( $name_prefix . '[title]', $element_data['title'], __( 'Enter Primary Label', 'eform_elm' ), 'large' ); ?></td>
						<td></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[subtitle]', __( 'Subtitle', 'eform_elm' ) ); ?></th>
						<td><?php $that->ui->text( $name_prefix . '[subtitle]', $element_data['subtitle'], __( 'Description Text (Optional)', 'eform_elm' ), 'large' ); ?></td>
						<td></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][vertical]', __( 'Label Alignment', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->toggle( $name_prefix . '[settings][vertical]', __( 'Vertical', 'eform_elm' ), __( 'Horizontal', 'eform_elm' ), $element_data['settings']['vertical'], '1' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'The alignment of the label(question) and options. Making Horizontal will show the label on left, whereas making vertical will show it on top.', 'eform_elm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][centered]', __( 'Center Content', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->toggle( $name_prefix . '[settings][centered]', __( 'Yes', 'eform_elm' ), __( 'No', 'eform_elm' ), $element_data['settings']['centered'], '1' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'If enabled, then labels and elements will be centered. This will force vertical the content.', 'eform_elm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][hidden_label]', __( 'Hide Label', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->toggle( $name_prefix . '[settings][hidden_label]', __( 'Yes', 'eform_elm' ), __( 'No', 'eform_elm' ), $element_data['settings']['hidden_label'], '1' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'If enabled, then label along with subtitle and description would be hidden on the form. It would be visible only on the summary table and on emails. When using this, place a meaningful text in the placeholder.', 'eform_elm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][placeholder]', __( 'Placeholder Text', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->text( $name_prefix . '[settings][placeholder]', $element_data['settings']['placeholder'], __( 'Disabled', 'eform_elm' ) ); ?>
						</td>
						<td><?php $that->ui->help( __( 'Text that is shown by default when the field is empty.', 'eform_elm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][icon]', __( 'Select Icon', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->icon_selector( $name_prefix . '[settings][icon]', $element_data['settings']['icon'], __( 'Do not use any icon', 'eform_elm' ) ); ?>
						</td>
						<td><?php $that->ui->help( __( 'Select the icon you want to appear before the text. Select none to disable.', 'eform_elm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[tooltip]', __( 'Tooltip', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->textarea( $name_prefix . '[tooltip]', $element_data['tooltip'], __( 'HTML Enabled', 'eform_elm' ) ); ?>
						</td>
						<td><?php $that->ui->help( __( 'If you want to show tooltip, then please enter it here. You can write custom HTML too. Leave empty to disable.', 'eform_elm' ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div id="<?php echo $tab_names; ?>_ifs">
			<table class="form-table">
				<tbody>
					<?php //$that->_helper_build_prefil_text( $name_prefix, $data ); ?>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][min]', __( 'Minumum Input', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->spinner( $name_prefix . '[settings][min]', $element_data['settings']['min'], __( 'Enter Min Value', 'eform_elm' ), 'large' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'Enter minimum input.', 'eform_elm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][max]', __( 'Maximum Input', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->spinner( $name_prefix . '[settings][max]', $element_data['settings']['max'], __( 'Enter Max Value', 'eform_elm' ), 'large' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'Enter Maximum input.', 'eform_elm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][step]', __( 'Input Step', 'eform_elm' ) ); ?></th>
						<td>
							<?php $that->ui->spinner( $name_prefix . '[settings][step]', $element_data['settings']['step'], __( 'Enter Step Value', 'eform_elm' ), 'large' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'Enter input step.', 'eform_elm' ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div id="<?php echo $tab_names; ?>_validation">
			<?php $that->build_validation( $name_prefix, $element_structure['validation'], $element_data['validation'] ); ?>
		</div>
		<div id="<?php echo $tab_names; ?>_logic">
			<?php $that->build_conditional( $name_prefix, $element_data['conditional'] ); ?>
		</div>
	</div>
		<?php
		$that->ui->textarea_linked_wp_editor( $name_prefix . '[description]', $element_data['description'], '' );
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
			$name_prefix . '[value]', $submission_data['value'], $element_data['settings']['placeholder'], $element_data['settings']['min'], $element_data['settings']['max'], $element_data['settings']['step'], $element_data['validation']['required']
		);
		$id = 'ipt_fsqm_form_' . $that->form_id . '_' . $element_structure['m_type'] . '_' . $key . '_wrap';
		// Get the icon
		ob_start();
		$that->ui->print_icon( $element_data['settings']['icon'], false );
		$icon = ob_get_clean();
		// Init the column
		$that->ui->column_head( $id, 'full', true, 'ipt_fsqm_container_currency', $element_data['tooltip'] );
		$that->ui->question_container( $name_prefix, $icon . $element_data['title'], $element_data['subtitle'], array( array( $that->ui, 'spinner' ), $params ), $element_data['validation']['required'], false, $element_data['settings']['vertical'], $element_data['description'], [], [], $element_data['settings']['hidden_label'], $element_data['settings']['centered'] );
		$that->ui->column_tail();
	}

	public function currency_cb_data( $element_definition, $key, $element_data, $element_structure, $name_prefix, $submission_data, $submission_structure, $that ) {
		$ui = IPT_Plugin_UIF_Front::instance();
		$img = '<img src="' . $ui->get_image_for_icon( $element_data['settings']['icon'] ) . '" height="16" width="16" /> ';
		?>
		<th style="<?php echo $that->email_styling['th']; ?>" colspan="2" scope="row">
			<?php echo $element_data['title']; ?><br /><span class="description" style="<?php echo $that->email_styling['description']; ?>"><?php echo $element_data['subtitle']; ?></span>
			<?php if ( $element_data['description'] !== '' ) : ?>
			<div class="ipt_uif_richtext">
				<?php echo apply_filters( 'ipt_uif_richtext', $element_data['description'] ); ?>
			</div>
			<?php endif; ?>
		</th>
		<td style="<?php echo $that->email_styling['icons']; ?>" class="icons">
			<?php echo $img; ?>
		</td>
		<td style="<?php echo $that->email_styling['td']; ?>" colspan="2">
			<?php echo '<code>' . $submission_data['value'] . '</code>'; ?>
		</td>
		<?php
	}

	public function currency_cb_value( $element, $submission, $type, $data, $key ) {
		if ( 'string' == $type ) {
			return $submission['value'];
		}
		return [
			'value' => $submission['value'],
		];
	}
}
