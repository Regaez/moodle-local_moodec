<?php
/**
 * Moodec Edit course Form
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

class moodec_edit_course_form extends moodleform {
	//Add elements to form
	public function definition() {
		global $CFG, $COURSE;

		$mform = $this->_form; // Don't forget the underscore!

		$groups = local_moodec_get_groups($COURSE->id);

		$mform->addElement('advcheckbox', 'show_in_store', get_string('show_in_store', 'local_moodec'), get_string('show_in_store_label', 'local_moodec'), array('group' => 1), array(0, 1));

		$mform->addElement('select', 'pricing_model', get_string('pricing_model', 'local_moodec'), array('simple'=>'Simple','variable'=>'Variable')); // Add elements to your form

		// Simple price model
		
		$mform->addElement('header', 'simple_header', 'Simple pricing');

		$mform->addElement('text', 'simple_price', get_string('simple_price', 'local_moodec')); // Add elements to your form
		$mform->setType('simple_price', PARAM_FLOAT); //Set type of element
		$mform->setDefault('simple_price', 0.00); //Default value
		$mform->disabledif('simple_price', 'pricing_model', 'neq', 'simple');

		$mform->addElement('text', 'simple_enrolment_duration', get_string('simple_enrolment_duration', 'local_moodec'), get_string('simple_enrolment_duration_help', 'local_moodec')); // Add elements to your form
		$mform->setType('simple_enrolment_duration', PARAM_INT); //Set type of element
		$mform->setDefault('simple_enrolment_duration', 0); //Default value
		$mform->addHelpButton('simple_enrolment_duration', 'simple_enrolment_duration', 'local_moodec');
		$mform->disabledif('simple_enrolment_duration', 'pricing_model', 'neq', 'simple');

		$mform->addElement('select', 'simple_group', get_string('simple_group', 'local_moodec'), $groups);
		$mform->addHelpButton('simple_group', 'simple_group', 'local_moodec');
		$mform->disabledif('simple_group_1', 'pricing_model', 'neq', 'simple');


		// Variable price model
		
		$mform->addElement('header', 'variable_header', 'Variable pricing');

		$mform->addElement('select', 'variable_tiers', get_string('variable_tiers', 'local_moodec'), array(2=>2,3=>3,4=>4,5=>5)); // Add elements to your form
		$mform->disabledif('variable_tiers', 'pricing_model', 'neq', 'variable');

			// TIER 1

			$mform->addElement('text', 'variable_name_1', get_string('variable_name', 'local_moodec', array('tier' => 1))); // Add elements to your form
			$mform->setType('variable_name_1', PARAM_TEXT); //Set type of element
			$mform->setDefault('variable_name_1', get_string('variable_name_default', 'local_moodec', array('tier' => 1))); //Default value
			$mform->disabledif('variable_name_1', 'pricing_model', 'neq', 'variable');

			$mform->addElement('text', 'variable_price_1', get_string('variable_price', 'local_moodec', array('tier' => 1))); // Add elements to your form
			$mform->setType('variable_price_1', PARAM_FLOAT); //Set type of element
			$mform->setDefault('variable_price_1', 0.00 ); //Default value
			$mform->disabledif('variable_price_1', 'pricing_model', 'neq', 'variable');

			$mform->addElement('text', 'variable_enrolment_duration_1', get_string('variable_enrolment_duration', 'local_moodec', array('tier' => 1))); // Add elements to your form
			$mform->setType('variable_enrolment_duration_1', PARAM_INT); //Set type of element
			$mform->setDefault('variable_enrolment_duration_1', 0); //Default value
			$mform->disabledif('variable_enrolment_duration_1', 'pricing_model', 'neq', 'variable');

			$mform->addElement('select', 'variable_group_1', get_string('variable_group', 'local_moodec', array('tier' => 1)), $groups);
			$mform->addHelpButton('variable_group_1', 'variable_group_title', 'local_moodec');
			$mform->disabledif('variable_group_1', 'pricing_model', 'neq', 'variable');

			// TIER 2
			
			$mform->addElement('text', 'variable_name_2', get_string('variable_name', 'local_moodec', array('tier' => 2))); // Add elements to your form
			$mform->setType('variable_name_2', PARAM_TEXT); //Set type of element
			$mform->setDefault('variable_name_2', get_string('variable_name_default', 'local_moodec', array('tier' => 2))); //Default value
			$mform->disabledif('variable_name_2', 'pricing_model', 'neq', 'variable');

			$mform->addElement('text', 'variable_price_2', get_string('variable_price', 'local_moodec', array('tier' => 2))); // Add elements to your form
			$mform->setType('variable_price_2', PARAM_FLOAT); //Set type of element
			$mform->setDefault('variable_price_2', 0.00); //Default value
			$mform->disabledif('variable_price_2', 'pricing_model', 'neq', 'variable');

			$mform->addElement('text', 'variable_enrolment_duration_2', get_string('variable_enrolment_duration', 'local_moodec', array('tier' => 2))); // Add elements to your form
			$mform->setType('variable_enrolment_duration_2', PARAM_INT); //Set type of element
			$mform->setDefault('variable_enrolment_duration_2', 0); //Default value
			$mform->disabledif('variable_enrolment_duration_2', 'pricing_model', 'neq', 'variable');

			$mform->addElement('select', 'variable_group_2', get_string('variable_group', 'local_moodec', array('tier' => 2)), $groups);
			$mform->addHelpButton('variable_group_2', 'variable_group_title', 'local_moodec');
			$mform->disabledif('variable_group_2', 'pricing_model', 'neq', 'variable');

			// TIER 3
			
			$mform->addElement('text', 'variable_name_3', get_string('variable_name', 'local_moodec', array('tier' => 3))); // Add elements to your form
			$mform->setType('variable_name_3', PARAM_TEXT); //Set type of element
			$mform->setDefault('variable_name_3', get_string('variable_name_default', 'local_moodec', array('tier' => 3))); //Default value
			$mform->disabledif('variable_name_3', 'pricing_model', 'neq', 'variable');
			$mform->disabledif('variable_name_3', 'variable_tiers', 'eq', 2);

			$mform->addElement('text', 'variable_price_3', get_string('variable_price', 'local_moodec', array('tier' => 3))); // Add elements to your form
			$mform->setType('variable_price_3', PARAM_FLOAT); //Set type of element
			$mform->setDefault('variable_price_3', 0.00); //Default value
			$mform->disabledif('variable_price_3', 'pricing_model', 'neq', 'variable');
			$mform->disabledif('variable_price_3', 'variable_tiers', 'eq', 2);

			$mform->addElement('text', 'variable_enrolment_duration_3', get_string('variable_enrolment_duration', 'local_moodec', array('tier' => 3))); // Add elements to your form
			$mform->setType('variable_enrolment_duration_3', PARAM_INT); //Set type of element
			$mform->setDefault('variable_enrolment_duration_3', 0); //Default value
			$mform->disabledif('variable_enrolment_duration_3', 'pricing_model', 'neq', 'variable');	
			$mform->disabledif('variable_enrolment_duration_3', 'variable_tiers', 'eq', 2);		

			$mform->addElement('select', 'variable_group_3', get_string('variable_group', 'local_moodec', array('tier' => 3)), $groups);
			$mform->addHelpButton('variable_group_3', 'variable_group_title', 'local_moodec');
			$mform->disabledif('variable_name_3', 'pricing_model', 'neq', 'variable');
			$mform->disabledif('variable_group_3', 'variable_tiers', 'eq', 2);

			// TIER 4

			$mform->addElement('text', 'variable_name_4', get_string('variable_name', 'local_moodec', array('tier' => 4))); // Add elements to your form
			$mform->setType('variable_name_4', PARAM_TEXT); //Set type of element
			$mform->setDefault('variable_name_4', get_string('variable_name_default', 'local_moodec', array('tier' => 4))); //Default value
			$mform->disabledif('variable_name_4', 'pricing_model', 'neq', 'variable');
			$mform->disabledif('variable_name_4', 'variable_tiers', 'eq', 2);
			$mform->disabledif('variable_name_4', 'variable_tiers', 'eq', 3);

			$mform->addElement('text', 'variable_price_4', get_string('variable_price', 'local_moodec', array('tier' => 4))); // Add elements to your form
			$mform->setType('variable_price_4', PARAM_FLOAT); //Set type of element
			$mform->setDefault('variable_price_4', 0.00); //Default value
			$mform->disabledif('variable_price_4', 'pricing_model', 'neq', 'variable');
			$mform->disabledif('variable_price_4', 'variable_tiers', 'eq', 2);
			$mform->disabledif('variable_price_4', 'variable_tiers', 'eq', 3);

			$mform->addElement('text', 'variable_enrolment_duration_4', get_string('variable_enrolment_duration', 'local_moodec', array('tier' => 4))); // Add elements to your form
			$mform->setType('variable_enrolment_duration_4', PARAM_INT); //Set type of element
			$mform->setDefault('variable_enrolment_duration_4', 0); //Default value
			$mform->disabledif('variable_enrolment_duration_4', 'pricing_model', 'neq', 'variable');	
			$mform->disabledif('variable_enrolment_duration_4', 'variable_tiers', 'eq', 2);
			$mform->disabledif('variable_enrolment_duration_4', 'variable_tiers', 'eq', 3);

			$mform->addElement('select', 'variable_group_4', get_string('variable_group', 'local_moodec', array('tier' => 4)), $groups);
			$mform->addHelpButton('variable_group_4', 'variable_group_title', 'local_moodec');
			$mform->disabledif('variable_group_4', 'pricing_model', 'neq', 'variable');
			$mform->disabledif('variable_group_4', 'variable_tiers', 'eq', 2);
			$mform->disabledif('variable_group_4', 'variable_tiers', 'eq', 3);

			// TIER 5

			$mform->addElement('text', 'variable_name_5', get_string('variable_name', 'local_moodec', array('tier' => 5))); // Add elements to your form
			$mform->setType('variable_name_5', PARAM_TEXT); //Set type of element
			$mform->setDefault('variable_name_5', get_string('variable_name_default', 'local_moodec', array('tier' => 5))); //Default value
			$mform->disabledif('variable_name_5', 'pricing_model', 'neq', 'variable');
			$mform->disabledif('variable_name_5', 'variable_tiers', 'eq', 2);
			$mform->disabledif('variable_name_5', 'variable_tiers', 'eq', 3);
			$mform->disabledif('variable_name_5', 'variable_tiers', 'eq', 4);

			$mform->addElement('text', 'variable_price_5', get_string('variable_price', 'local_moodec', array('tier' => 5))); // Add elements to your form
			$mform->setType('variable_price_5', PARAM_FLOAT); //Set type of element
			$mform->setDefault('variable_price_5', 0.00); //Default value
			$mform->disabledif('variable_price_5', 'pricing_model', 'neq', 'variable');
			$mform->disabledif('variable_price_5', 'variable_tiers', 'eq', 2);
			$mform->disabledif('variable_price_5', 'variable_tiers', 'eq', 3);
			$mform->disabledif('variable_price_5', 'variable_tiers', 'eq', 4);

			$mform->addElement('text', 'variable_enrolment_duration_5', get_string('variable_enrolment_duration', 'local_moodec', array('tier' => 5))); // Add elements to your form
			$mform->setType('variable_enrolment_duration_5', PARAM_INT); //Set type of element
			$mform->setDefault('variable_enrolment_duration_5', 0); //Default value
			$mform->disabledif('variable_enrolment_duration_5', 'pricing_model', 'neq', 'variable');	
			$mform->disabledif('variable_enrolment_duration_5', 'variable_tiers', 'eq', 2);
			$mform->disabledif('variable_enrolment_duration_5', 'variable_tiers', 'eq', 3);
			$mform->disabledif('variable_enrolment_duration_5', 'variable_tiers', 'eq', 4);

			$mform->addElement('select', 'variable_group_5', get_string('variable_group', 'local_moodec', array('tier' => 5)), $groups);
			$mform->addHelpButton('variable_group_5', 'variable_group_title', 'local_moodec');
			$mform->disabledif('variable_group_5', 'pricing_model', 'neq', 'variable');
			$mform->disabledif('variable_group_5', 'variable_tiers', 'eq', 2);
			$mform->disabledif('variable_group_5', 'variable_tiers', 'eq', 3);
			$mform->disabledif('variable_group_5', 'variable_tiers', 'eq', 4);

		// More settings
		
		$mform->addElement('header', 'more_header', 'More settings');

		$mform->addElement('editor', 'additional_info', get_string('additional_info', 'local_moodec'));
		$mform->setType('additional_info', PARAM_RAW);

		$mform->addElement('text', 'product_tags', get_string('product_tags', 'local_moodec')); // Add elements to your form
		$mform->setType('product_tags', PARAM_TEXT); //Set type of element
		$mform->addHelpButton('product_tags', 'product_tags_title', 'local_moodec');

		$this->add_action_buttons();

		$mform->addElement('hidden', 'id', null);
		$mform->setType('id', PARAM_INT);

	}
	//Custom validation should be added here
	function validation($data, $files) {
		// TODO: add validation
		return array();
	}
}