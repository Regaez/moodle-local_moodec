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

class moodec_edit_course_form extends moodleform {
	//Add elements to form
	public function definition() {
		global $CFG;

		$mform = $this->_form; // Don't forget the underscore!

		$mform->addElement('advcheckbox', 'show_in_store', get_string('show_in_store', 'local_moodec'), get_string('show_in_store_label', 'local_moodec'), array('group' => 1), array(0, 1));

		$mform->addElement('text', 'price', get_string('form_price', 'local_moodec')); // Add elements to your form
		$mform->setType('price', PARAM_FLOAT); //Set type of element
		$mform->setDefault('price', get_string('form_price_default', 'local_moodec')); //Default value

		$mform->addElement('text', 'enrolment_duration', get_string('enrolment_duration', 'local_moodec'), get_string('enrolment_duration_help', 'local_moodec')); // Add elements to your form
		$mform->setType('enrolment_duration', PARAM_INT); //Set type of element
		$mform->setDefault('enrolment_duration', get_string('enrolment_duration_default', 'local_moodec')); //Default value

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