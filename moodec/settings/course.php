<?php
/**
 * Moodec Course Settings
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(__FILE__) . '/../../../config.php';
require_once $CFG->dirroot . '/local/moodec/lib.php';
require_once $CFG->dirroot . '/local/moodec/forms/editcourse.php';

$courseid = optional_param('id', 0, PARAM_INT);

$PAGE->set_pagelayout('admin');

if ($courseid) {
	$PAGE->set_url('/local/moodec/settings/course.php', array('id' => $courseid));
	$course = get_course($courseid);
	require_login($course);
	$course = course_get_format($course)->get_course();

	if (!($course = $DB->get_record('course', array('id' => $courseid)))) {
		print_error('invalidcourseid', 'error');
	}

	context_helper::preload_course($course->id);
	if (!$context = context_course::instance($course->id)) {
		print_error('nocontext');
	}

	require_capability('moodle/course:update', $context);
} else {
	require_login();
}

// instantiate edit_course form
$mform = new moodec_edit_course_form();

if ($mform->is_cancelled()) {
	// redirect back to the course page
	redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
} else if ($data = $mform->get_data()) {
	//In this case you process validated data. $mform->get_data() returns data posted in form.
	global $DB;
	$result = false;

	// remove submitbutton from data object
	unset($data->submitbutton);

	// check to see if the course already has settings
	$recordExists = $DB->get_record('local_moodec_course', array('courseid' => $data->id), '*', IGNORE_MISSING);

	// set the courseid field to our form's course id field
	$data->courseid = $data->id;

	if (!!$recordExists) {
		// set the id to the record returned by our earlier query
		$data->id = $recordExists->id;
		$result = $DB->update_record('local_moodec_course', $data);
	} else {
		// remove the id field as it will be auto generated on insert
		unset($data->id);
		$result = $DB->insert_record('local_moodec_course', $data);
	}

	if (!!$result) {
		// redirect back to the course page
		redirect(new moodle_url('/course/view.php', array('id' => $data->courseid)));
	} else {
		// TODO: throw exception
		echo 'something went wrong...';
	}
} else {
	// get existing data, if any
	$existingData = $DB->get_record('local_moodec_course', array('courseid' => $courseid), '*', IGNORE_MISSING);

	$toform = new stdClass();

	// set it to be the form defaults
	if (!!$existingData) {
		$toform->id = $existingData->courseid;
		$toform->show_in_store = $existingData->show_in_store;
		$toform->price = $existingData->price;
		$toform->enrolment_duration = $existingData->enrolment_duration;
	} else {
		// we need to send the courseid
		$toform->id = $courseid;
	}
	$mform->set_data($toform);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('edit_course_form_title', 'local_moodec', array('name' => $course->fullname)));

$mform->display();

echo $OUTPUT->footer();
