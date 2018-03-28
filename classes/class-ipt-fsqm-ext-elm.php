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
			'olabel' => __( 'Options', 'fsqm_elm' ),
			'clabel' => __( 'Count', 'fsqm_elm' ),
		) );
	}


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
	public function ipicker_report_cb( $do_data, $element_data ) {
		$iconpath = plugins_url( '/lib/images/icomoon/333/PNG/', IPT_FSQM_Loader::$abs_file );
		$ui = IPT_Plugin_UIF_Front::instance();
		$data = array(
			'icon1' => 0,
			'icon2' => 0,
		);
		?>
<table class="ipt_fsqm_preview table_to_update">
	<thead>
		<tr>
			<th style="width: 50%"><?php _e( 'Graphical Representation', 'ipt_cs' ); ?></th>
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
							<td><?php echo '<img src="' . $iconpath . $ui->get_icon_image_name( $element_data['settings']['icon1'] ) . '" height="16" width="16" /> '; ?></td>
							<th><?php echo $element_data['settings']['icon1_label']; ?></th>
							<td class="icon1">0</td>
						</tr>
						<tr>
							<td><?php echo '<img src="' . $iconpath . $ui->get_icon_image_name( $element_data['settings']['icon2'] ) . '" height="16" width="16" /> '; ?></td>
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
	public function ipicker_report_cal_cb( $element, $data, $m_key, $do_data, $return, $obj ) {
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
		// 	'icon1_label' => __( 'Heart filled with love', 'fsqm_elm' ),
		// 	'icon2' => 0xe0d3,
		// 	'icon2_label' => __( 'Broken Heart', 'fsqm_elm' ),
		// ),
		$that->ui->textarea_linked_wp_editor( $name_prefix . '[description]', $element_data['description'], '' );
		$tab_names = $that->ui->generate_id_from_name( $name_prefix ) . '_settings_tab_';
?>
	<div class="ipt_uif_tabs">
		<ul>
			<li><a href="#<?php echo $tab_names; ?>_elm"><?php _e( 'Appearance', 'ipt_fsqm' ); ?></a></li>
			<li><a href="#<?php echo $tab_names; ?>_ifs"><?php _e( 'Interface', 'ipt_fsqm' ); ?></a></li>
			<li><a href="#<?php echo $tab_names; ?>_validation"><?php _e( 'Validation', 'ipt_fsqm' ); ?></a></li>
			<li><a href="#<?php echo $tab_names; ?>_logic"><?php _e( 'Logic', 'ipt_fsqm' ); ?></a></li>
		</ul>
		<div id="<?php echo $tab_names; ?>_elm">
			<table class="form-table">
				<tbody>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[title]', __( 'Title', 'ipt_fsqm' ) ); ?></th>
						<td><?php $that->ui->text( $name_prefix . '[title]', $data['title'], __( 'Enter Primary Label', 'ipt_fsqm' ), 'large' ); ?></td>
						<td></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[subtitle]', __( 'Subtitle', 'ipt_fsqm' ) ); ?></th>
						<td><?php $that->ui->text( $name_prefix . '[subtitle]', $data['subtitle'], __( 'Description Text (Optional)', 'ipt_fsqm' ), 'large' ); ?></td>
						<td></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][vertical]', __( 'Label Alignment', 'ipt_fsqm' ) ); ?></th>
						<td>
							<?php $that->ui->toggle( $name_prefix . '[settings][vertical]', __( 'Vertical', 'ipt_fsqm' ), __( 'Horizontal', 'ipt_fsqm' ), $data['settings']['vertical'], '1' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'The alignment of the label(question) and options. Making Horizontal will show the label on left, whereas making vertical will show it on top.', 'ipt_fsqm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][centered]', __( 'Center Content', 'ipt_fsqm' ) ); ?></th>
						<td>
							<?php $that->ui->toggle( $name_prefix . '[settings][centered]', __( 'Yes', 'ipt_fsqm' ), __( 'No', 'ipt_fsqm' ), $data['settings']['centered'], '1' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'If enabled, then labels and elements will be centered. This will force vertical the content.', 'ipt_fsqm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][hidden_label]', __( 'Hide Label', 'ipt_fsqm' ) ); ?></th>
						<td>
							<?php $that->ui->toggle( $name_prefix . '[settings][hidden_label]', __( 'Yes', 'ipt_fsqm' ), __( 'No', 'ipt_fsqm' ), $data['settings']['hidden_label'], '1' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'If enabled, then label along with subtitle and description would be hidden on the form. It would be visible only on the summary table and on emails. When using this, place a meaningful text in the placeholder.', 'ipt_fsqm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][placeholder]', __( 'Placeholder Text', 'ipt_fsqm' ) ); ?></th>
						<td>
							<?php $that->ui->text( $name_prefix . '[settings][placeholder]', $data['settings']['placeholder'], __( 'Disabled', 'ipt_fsqm' ) ); ?>
						</td>
						<td><?php $that->ui->help( __( 'Text that is shown by default when the field is empty.', 'ipt_fsqm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][icon]', __( 'Select Icon', 'ipt_fsqm' ) ); ?></th>
						<td>
							<?php $that->ui->icon_selector( $name_prefix . '[settings][icon]', $data['settings']['icon'], __( 'Do not use any icon', 'ipt_fsqm' ) ); ?>
						</td>
						<td><?php $that->ui->help( __( 'Select the icon you want to appear before the text. Select none to disable.', 'ipt_fsqm' ) ) ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[tooltip]', __( 'Tooltip', 'ipt_fsqm' ) ); ?></th>
						<td>
							<?php $that->ui->textarea( $name_prefix . '[tooltip]', $data['tooltip'], __( 'HTML Enabled', 'ipt_fsqm' ) ); ?>
						</td>
						<td><?php $that->ui->help( __( 'If you want to show tooltip, then please enter it here. You can write custom HTML too. Leave empty to disable.', 'ipt_fsqm' ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div id="<?php echo $tab_names; ?>_ifs">
			<table class="form-table">
				<tbody>
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
		</div>
		<div id="<?php echo $tab_names; ?>_validation">
			<?php $that->build_validation( $name_prefix, $element_structure['validation'], $data['validation'] ); ?>
		</div>
		<div id="<?php echo $tab_names; ?>_logic">
			<?php $that->build_conditional( $name_prefix, $data['conditional'] ); ?>
		</div>
	</div>
	<?php
		$that->ui->textarea_linked_wp_editor( $name_prefix . '[description]', $data['description'], '' );
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
		$checked = '<img src="' . $that->icon_path . 'radio-checked.png" height="16" width="16" />';
		$unchecked = '<img src="' . $that->icon_path . 'radio-unchecked.png" height="16" width="16" />';
		$ui = IPT_Plugin_UIF_Front::instance();
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
			<?php echo '<img src="' . $that->icon_path . $ui->get_icon_image_name( $element_data['settings']['icon1'] ) . '" height="16" width="16" /> ' . $element_data['settings']['icon1_label']; ?>
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
			<?php echo '<img src="' . $that->icon_path . $ui->get_icon_image_name( $element_data['settings']['icon2'] ) . '" height="16" width="16" /> ' . $element_data['settings']['icon2_label']; ?>
		</td>
		<?php
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

	public function currency_report_cb( $element_data ) {
		$pinfo_titles = array(
			'name' => __( 'Name', 'fsqm_elm' ),
			'email' => __( 'Email', 'fsqm_elm' ),
			'phone' => __( 'Phone', 'fsqm_elm' ),
		);

		$data = array();

		?>
<table class="ipt_fsqm_preview">
	<thead>
		<tr>
			<th style="width: 40%;"><?php _e( 'Feedback', 'fsqm_elm' ); ?></th>
			<?php foreach ( $pinfo_titles as $p_val ) : ?>
			<th><?php echo $p_val; ?></th>
			<?php endforeach; ?>
			<th><?php _e( 'Date', 'fsqm_elm' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr class="empty">
			<td colspan="5"><?php _e( 'No data yet!', 'fsqm_elm' ); ?></td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<th style="width: 40%;"><?php _e( 'Feedback', 'fsqm_elm' ); ?></th>
			<?php foreach ( $pinfo_titles as $p_val ) : ?>
			<th><?php echo $p_val; ?></th>
			<?php endforeach; ?>
			<th><?php _e( 'Date', 'fsqm_elm' ); ?></th>
		</tr>
	</tfoot>
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
	public function currency_report_cal_cb( $element, $data, $m_key, $do_data, $return, $obj ) {
		if ( empty( $data['value'] ) ) {
			return $return;
		}
		$return[] = array(
			'value' => $data['value'],
			'name'  => $obj->data->f_name . ' ' . $obj->data->l_name,
			'email' => $obj->data->email == '' ? __( 'anonymous', 'ipt_fsqm' ) : '<a href="mailto:' . $obj->data->email . '">' . $obj->data->email . '</a>',
			'phone' => $obj->data->phone,
			'date'  => date_i18n( get_option( 'date_format' ) . __(' \a\t ', 'ipt_fsqm') . get_option( 'time_format' ), strtotime( $obj->data->date ) ),
			'id'    => $obj->data_id,
		);

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
		$that->ui->textarea_linked_wp_editor( $name_prefix . '[description]', $element_data['description'], '' );
		$that->ui->textarea_linked_wp_editor( $name_prefix . '[description]', $element_data['description'], '' );
		?>
	<div class="ipt_uif_tabs">
		<ul>
			<li><a href="#<?php echo $tab_names; ?>_elm"><?php _e( 'Appearance', 'ipt_fsqm' ); ?></a></li>
			<li><a href="#<?php echo $tab_names; ?>_ifs"><?php _e( 'Interface', 'ipt_fsqm' ); ?></a></li>
			<li><a href="#<?php echo $tab_names; ?>_validation"><?php _e( 'Validation', 'ipt_fsqm' ); ?></a></li>
			<li><a href="#<?php echo $tab_names; ?>_logic"><?php _e( 'Logic', 'ipt_fsqm' ); ?></a></li>
		</ul>
		<div id="<?php echo $tab_names; ?>_elm">
			<table class="form-table">
				<tbody>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[title]', __( 'Title', 'ipt_fsqm' ) ); ?></th>
						<td><?php $that->ui->text( $name_prefix . '[title]', $data['title'], __( 'Enter Primary Label', 'ipt_fsqm' ), 'large' ); ?></td>
						<td></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[subtitle]', __( 'Subtitle', 'ipt_fsqm' ) ); ?></th>
						<td><?php $that->ui->text( $name_prefix . '[subtitle]', $data['subtitle'], __( 'Description Text (Optional)', 'ipt_fsqm' ), 'large' ); ?></td>
						<td></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][vertical]', __( 'Label Alignment', 'ipt_fsqm' ) ); ?></th>
						<td>
							<?php $that->ui->toggle( $name_prefix . '[settings][vertical]', __( 'Vertical', 'ipt_fsqm' ), __( 'Horizontal', 'ipt_fsqm' ), $data['settings']['vertical'], '1' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'The alignment of the label(question) and options. Making Horizontal will show the label on left, whereas making vertical will show it on top.', 'ipt_fsqm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][centered]', __( 'Center Content', 'ipt_fsqm' ) ); ?></th>
						<td>
							<?php $that->ui->toggle( $name_prefix . '[settings][centered]', __( 'Yes', 'ipt_fsqm' ), __( 'No', 'ipt_fsqm' ), $data['settings']['centered'], '1' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'If enabled, then labels and elements will be centered. This will force vertical the content.', 'ipt_fsqm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][hidden_label]', __( 'Hide Label', 'ipt_fsqm' ) ); ?></th>
						<td>
							<?php $that->ui->toggle( $name_prefix . '[settings][hidden_label]', __( 'Yes', 'ipt_fsqm' ), __( 'No', 'ipt_fsqm' ), $data['settings']['hidden_label'], '1' ); ?>
						</td>
						<td><?php $that->ui->help( __( 'If enabled, then label along with subtitle and description would be hidden on the form. It would be visible only on the summary table and on emails. When using this, place a meaningful text in the placeholder.', 'ipt_fsqm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][placeholder]', __( 'Placeholder Text', 'ipt_fsqm' ) ); ?></th>
						<td>
							<?php $that->ui->text( $name_prefix . '[settings][placeholder]', $data['settings']['placeholder'], __( 'Disabled', 'ipt_fsqm' ) ); ?>
						</td>
						<td><?php $that->ui->help( __( 'Text that is shown by default when the field is empty.', 'ipt_fsqm' ) ); ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][icon]', __( 'Select Icon', 'ipt_fsqm' ) ); ?></th>
						<td>
							<?php $that->ui->icon_selector( $name_prefix . '[settings][icon]', $data['settings']['icon'], __( 'Do not use any icon', 'ipt_fsqm' ) ); ?>
						</td>
						<td><?php $that->ui->help( __( 'Select the icon you want to appear before the text. Select none to disable.', 'ipt_fsqm' ) ) ?></td>
					</tr>
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[tooltip]', __( 'Tooltip', 'ipt_fsqm' ) ); ?></th>
						<td>
							<?php $that->ui->textarea( $name_prefix . '[tooltip]', $data['tooltip'], __( 'HTML Enabled', 'ipt_fsqm' ) ); ?>
						</td>
						<td><?php $that->ui->help( __( 'If you want to show tooltip, then please enter it here. You can write custom HTML too. Leave empty to disable.', 'ipt_fsqm' ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div id="<?php echo $tab_names; ?>_ifs">
			<table class="form-table">
				<tbody>
					<?php //$that->_helper_build_prefil_text( $name_prefix, $data ); ?>
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
					<tr>
						<th><?php $that->ui->generate_label( $name_prefix . '[settings][readonly]', __( 'Readonly', 'ipt_fsqm' ) ); ?></th>
						<td>
							<?php $that->ui->toggle( $name_prefix . '[settings][readonly]', __( 'Yes', 'ipt_fsqm' ), __( 'No', 'ipt_fsqm' ), $data['settings']['readonly'] ); ?>
						</td>
						<td><?php $that->ui->help( __( 'If enabled, then the recorded value would not be editable by user. Make sure the validation matches, otherwise it might lead to error.', 'ipt_fsqm' ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div id="<?php echo $tab_names; ?>_validation">
			<?php $that->build_validation( $name_prefix, $element_structure['validation'], $data['validation'] ); ?>
		</div>
		<div id="<?php echo $tab_names; ?>_logic">
			<?php $that->build_conditional( $name_prefix, $data['conditional'] ); ?>
		</div>
	</div>
		<?php
		$that->ui->textarea_linked_wp_editor( $name_prefix . '[description]', $data['description'], '' );
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
		$ui = IPT_Plugin_UIF_Front::instance();
		$img = '<img src="' . $that->icon_path . $ui->get_icon_image_name( $element_data['settings']['icon'] ) . '" height="16" width="16" /> ';
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
}
