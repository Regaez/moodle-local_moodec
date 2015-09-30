<?php
/**
 * Moodec Edit Product Form
 *
 * @package     local
 * @subpackage  local_moodec
 * @author      Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(__FILE__) . '/../../../config.php';

//moodleform is defined in formslib.php
require_once $CFG->libdir . '/formslib.php';

require_once dirname(__FILE__) . '/../lib.php';

class moodec_edit_product_form extends moodleform {
	
	//Add elements to form
	public function definition() {
		global $CFG, $DB, $COURSE, $PAGE;

		$mform = $this->_form; // Don't forget the underscore!

		// We are repurposing the course format autoreload script so 
		// the page reloads when the number of variations changes
		$PAGE->requires->yui_module('moodle-course-formatchooser', 'M.course.init_formatchooser',
		        array(array('formid' => $mform->getAttribute('id'))));
		// And we check the params for format (will be passed on page reload)
		// so we know how many variations to display
		$variationCount = optional_param('format', 0, PARAM_INT);

		$groups = local_moodec_get_groups($COURSE->id);

		$productconfig = $DB->get_record('local_moodec_product', array('course_id' => $COURSE->id), '*', IGNORE_MISSING);

		/** 
		 * PRODUCT ENABLED FIELD
		 * @var Checkbox
		 */
		$mform->addElement(
			'advcheckbox',
			'product_enabled',
			get_string(
				'product_enabled',
				'local_moodec'
			), 
			get_string(
				'product_enabled_label',
				'local_moodec'
			), 
			array(
				'group' => 1
			), 
			array(0, 1)
		);

		/** 
		 * PRODUCT ADDITIONAL INFO FIELD
		 * @var HTML Editor
		 */
		$mform->addElement(
			'editor',
			'product_description',
			get_string(
				'product_description',
				'local_moodec'
			)
		);
		$mform->setType('product_description', PARAM_RAW);
		$mform->addHelpButton(
			'product_description',
			'product_description',
			'local_moodec'
		);

		/** 
		 * PRODUCT TAGS FIELD
		 * @var Textbox
		 */
		$mform->addElement(
			'text',
			'product_tags',
			get_string(
				'product_tags',
				'local_moodec'
			)
		);
		$mform->setType('product_tags', PARAM_TEXT);
		$mform->addHelpButton(
			'product_tags',
			'product_tags',
			'local_moodec'
		);

		/** 
		 * PRODUCT TYPE FIELD
		 * @var Select
		 */
		$mform->addElement(
			'select',
			'product_type',
			get_string(
				'product_type',
				'local_moodec'
			),
			array(
				PRODUCT_TYPE_SIMPLE => get_string('product_type_simple_label', 'local_moodec'),
				PRODUCT_TYPE_VARIABLE => get_string('product_type_variable_label', 'local_moodec')
			)
		);
		$mform->addHelpButton(
			'product_type',
			'product_type',
			'local_moodec'
		);

		/** 
		 * PRODUCT VARIATION COUNT FIELD
		 * @var Select
		 */
		$mform->addElement(
			'select',
			'format',
			get_string(
				'product_variation_count',
				'local_moodec'
			),
			array(
				1 => 1,
				2 => 2,
				3 => 3,
				4 => 4,
				5 => 5,
				6 => 6,
				7 => 7,
				8 => 8,
				9 => 9,
				10 => 10
			)
		);
		$mform->addHelpButton('format', 'product_variation_count', 'local_moodec');
		$mform->disabledif('format', 'product_type', 'neq', PRODUCT_TYPE_VARIABLE);

		// If the product has been saved before, then it will have config settings
		// If it hasn't been saved, then it's okay to only show 1 variation
		if(!!$productconfig) {
			// If it's a variable product, with multiple variations, and variationCount is equal to 1 
			// (this indicates the number of variations has not been specified via optional_param)
			// Then we set the number of variations displayed, to be the amount in the product config
			// If variationCount evaluates to false, then it means the user has changed the amount of variations for this product, so we show the desired amount
			if($productconfig->type === PRODUCT_TYPE_VARIABLE && !$variationCount ) {
				$variationCount = $productconfig->variation_count;
			}
		}

		// If false, then we set the count to a default so there's always at least 1 option
		if( !$variationCount ) {
			$variationCount = 1;
		}

		$mform->setDefault('format', $variationCount);

		// Button to update format-specific options on format change (will be hidden by JavaScript).
		$mform->registerNoSubmitButton('updatecourseformat');
		$mform->addElement(
			'submit',
			'updatecourseformat',
			get_string(
				'product_variations_update',
				'local_moodec'
			)
		);

		for ($i = 1; $i <= $variationCount; $i++) { 
			
			$mform->addElement(
				'header',
				'product_variation_header_'.$i,
				get_string(
					'product_variation_header',
					'local_moodec',
					array(
						'count' => $i
					)
				)
			);

			// The first variation is mandatory, so we don't show enabled field
			if( 1 < $i) {
				
				/** 
				 * PRODUCT VARIATION ENABLED FIELD
				 * @var Textbox
				 */
				$mform->addElement(
					'advcheckbox',
					'product_variation_enabled_'.$i,
					get_string(
						'product_variation_enabled',
						'local_moodec'
					), 
					get_string(
						'product_variation_enabled_label',
						'local_moodec'
					), 
					array(
						'group' => 1
					), 
					array(0, 1)
				);
				$mform->disabledif('product_variation_enabled_'.$i, 'product_type', 'neq', PRODUCT_TYPE_VARIABLE);
			}

			/** 
			 * PRODUCT VARIATION NAME FIELD
			 * @var Textbox
			 */
			$mform->addElement(
				'text',
				'product_variation_name_'.$i,
				get_string(
					'product_variation_name',
					'local_moodec'
				),
				array(
					'maxlength' => 25
				)
			);
			$mform->setType('product_variation_name_'.$i, PARAM_TEXT);
			$mform->addHelpButton(
				'product_variation_name_'.$i,
				'product_variation_name',
				'local_moodec'
			);
			if( 1 < $i) {
				$mform->disabledif('product_variation_name_'.$i, 'product_type', 'neq', PRODUCT_TYPE_VARIABLE);
			}

			/** 
			 * PRODUCT VARIATION PRICE FIELD
			 * @var Textbox
			 */
			$mform->addElement(
				'text',
				'product_variation_price_'.$i,
				get_string(
					'product_variation_price',
					'local_moodec'
				),
				array(
					'maxlength' => 9
				)
			);
			$mform->setType('product_variation_price_'.$i, PARAM_TEXT);
			$mform->addHelpButton(
				'product_variation_price_'.$i,
				'product_variation_price',
				'local_moodec'
			);
			if( 1 < $i) {
				$mform->disabledif('product_variation_price_'.$i, 'product_type', 'neq', PRODUCT_TYPE_VARIABLE);
			}

			/** 
			 * PRODUCT VARIATION ENROLMENT DURATION FIELD
			 * @var Textbox
			 */
			$mform->addElement(
				'text',
				'product_variation_duration_'.$i,
				get_string(
					'product_variation_duration',
					'local_moodec'
				),
				array(
					'maxlength' => 8
				)
			);
			$mform->setType('product_variation_duration_'.$i, PARAM_TEXT);
			$mform->addHelpButton(
				'product_variation_duration_'.$i,
				'product_variation_duration',
				'local_moodec'
			);
			if( 1 < $i) {
				$mform->disabledif('product_variation_duration_'.$i, 'product_type', 'neq', PRODUCT_TYPE_VARIABLE);
			}

			/** 
			 * PRODUCT VARIATION GROUP FIELD
			 * @var Select
			 */
			$mform->addElement(
				'select',
				'product_variation_group_'.$i,
				get_string(
					'product_variation_group',
					'local_moodec'
				),
				$groups
			);
			$mform->addHelpButton(
				'product_variation_group_'.$i,
				'product_variation_group',
				'local_moodec'
			);
			if( 1 < $i) {
				$mform->disabledif('product_variation_group_'.$i, 'product_type', 'neq', PRODUCT_TYPE_VARIABLE);
			}

		}


		// FORM BUTTONS
		$this->add_action_buttons();

		$mform->addElement('hidden', 'id', null);
		$mform->setType('id', PARAM_INT);
	}

	//Custom validation should be added here
	function validation($data, $files) {
		// TODO: add validation
		
		$errors = parent::validation($data, $files);
		
		$numVars = $data['product_type'] === PRODUCT_TYPE_SIMPLE ? 1 : (int) $data['format'];

		for ($i=1; $i <= $numVars; $i++) { 
			
			$name = 'product_variation_name_' . $i;
			$price = 'product_variation_price_' . $i;
			$duration = 'product_variation_duration_' . $i;

			if( empty($data[$name]) && $data['product_type'] !== PRODUCT_TYPE_SIMPLE ) {
				$errors[$name] = get_string('error_invalid_name', 'local_moodec');
			}

			if( !is_numeric($data[$price]) && !is_float($data[$price]) ) {
				$errors[$price] = get_string('error_invalid_price', 'local_moodec');
			}

			if( !is_numeric($data[$duration]) && !is_int($data[$duration]) ) {
				$errors[$duration] = get_string('error_invalid_duration', 'local_moodec');
			}
						
		}

		return $errors;
	}
}
