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
$PAGE->requires->jquery();

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

	$data->additional_info = $data->additional_info['text'];

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

		$toform->pricing_model = $existingData->pricing_model;

		$toform->simple_price = $existingData->simple_price;
		$toform->simple_enrolment_duration = $existingData->simple_enrolment_duration;
		$toform->simple_group = $existingData->simple_group;

		$toform->variable_tiers = $existingData->variable_tiers;

		$toform->variable_name_1 = $existingData->variable_name_1;
		$toform->variable_price_1 = $existingData->variable_price_1;
		$toform->variable_enrolment_duration_1 = $existingData->variable_enrolment_duration_1;
		$toform->variable_group_1 = $existingData->variable_group_1;

		$toform->variable_name_2 = $existingData->variable_name_2;
		$toform->variable_price_2 = $existingData->variable_price_2;
		$toform->variable_enrolment_duration_2 = $existingData->variable_enrolment_duration_2;
		$toform->variable_group_2 = $existingData->variable_group_2;

		$toform->variable_name_3 = $existingData->variable_name_3;
		$toform->variable_price_3 = $existingData->variable_price_3;
		$toform->variable_enrolment_duration_3 = $existingData->variable_enrolment_duration_3;
		$toform->variable_group_3 = $existingData->variable_group_3;

		$toform->variable_name_4 = $existingData->variable_name_4;
		$toform->variable_price_4 = $existingData->variable_price_4;
		$toform->variable_enrolment_duration_4 = $existingData->variable_enrolment_duration_4;
		$toform->variable_group_4 = $existingData->variable_group_4;

		$toform->variable_name_5 = $existingData->variable_name_5;
		$toform->variable_price_5 = $existingData->variable_price_5;
		$toform->variable_enrolment_duration_5 = $existingData->variable_enrolment_duration_5;
		$toform->variable_group_5 = $existingData->variable_group_5;

		$toform->additional_info['text'] = $existingData->additional_info;
		$toform->additional_info['format'] = 1;

		$toform->product_tags = $existingData->product_tags;
	} else {
		// we need to send the courseid
		$toform->id = $courseid;
	}
	$mform->set_data($toform);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('edit_course_form_title', 'local_moodec', array('name' => $course->fullname)));

$mform->display(); ?>

<script>
	$('#id_pricing_model').on('change', function(){
		if($('#id_pricing_model').val() !== 'simple') {
			$('#id_simple_header').hide();
		} else {
			$('#id_simple_header').show().removeClass('collapsed');
		}

		if($('#id_pricing_model').val() !== 'variable') {
			$('#id_variable_header').hide();
		} else {
			$('#id_variable_header').show().removeClass('collapsed');
		}
	});
	$('#id_variable_tiers').on('change', function(){
		if($('#id_variable_tiers').val() < 3) {
			$('#fitem_id_variable_name_3').hide();
			$('#fitem_id_variable_group_3').hide();
			$('#fitem_id_variable_price_3').hide();
			$('#fitem_id_variable_enrolment_duration_3').hide();
		} else {
			$('#fitem_id_variable_name_3').show();
			$('#fitem_id_variable_group_3').show();
			$('#fitem_id_variable_price_3').show();
			$('#fitem_id_variable_enrolment_duration_3').show();
		}

		if($('#id_variable_tiers').val() < 4) {
			$('#fitem_id_variable_name_4').hide();
			$('#fitem_id_variable_group_4').hide();
			$('#fitem_id_variable_price_4').hide();
			$('#fitem_id_variable_enrolment_duration_4').hide();
		} else {
			$('#fitem_id_variable_name_4').show();
			$('#fitem_id_variable_group_4').show();
			$('#fitem_id_variable_price_4').show();
			$('#fitem_id_variable_enrolment_duration_4').show();
		}

		if($('#id_variable_tiers').val() < 5) {
			$('#fitem_id_variable_name_5').hide();
			$('#fitem_id_variable_group_5').hide();
			$('#fitem_id_variable_price_5').hide();
			$('#fitem_id_variable_enrolment_duration_5').hide();
		} else {
			$('#fitem_id_variable_name_5').show();
			$('#fitem_id_variable_group_5').show();
			$('#fitem_id_variable_price_5').show();
			$('#fitem_id_variable_enrolment_duration_5').show();
		}
	});

	var initSettings = function() {
		if($('#id_pricing_model').val() !== 'simple') {
			$('#id_simple_header').hide();
		}

		if($('#id_pricing_model').val() !== 'variable') {
			$('#id_variable_header').hide();
		}

		if($('#id_variable_tiers').val() < 3) {
			$('#fitem_id_variable_name_3').hide();
			$('#fitem_id_variable_group_3').hide();
			$('#fitem_id_variable_price_3').hide();
			$('#fitem_id_variable_enrolment_duration_3').hide();
		}

		if($('#id_variable_tiers').val() < 4) {
			$('#fitem_id_variable_name_4').hide();
			$('#fitem_id_variable_group_4').hide();
			$('#fitem_id_variable_price_4').hide();
			$('#fitem_id_variable_enrolment_duration_4').hide();
		}

		if($('#id_variable_tiers').val() < 5) {
			$('#fitem_id_variable_name_5').hide();
			$('#fitem_id_variable_group_5').hide();
			$('#fitem_id_variable_price_5').hide();
			$('#fitem_id_variable_enrolment_duration_5').hide();
		}

		$('#fitem_id_variable_tiers').addClass('course-settings-tier');
		$('#fitem_id_variable_group_1').addClass('course-settings-tier');
		$('#fitem_id_variable_group_2').addClass('course-settings-tier');
		$('#fitem_id_variable_group_3').addClass('course-settings-tier');
		$('#fitem_id_variable_group_4').addClass('course-settings-tier');
		$('#fitem_id_variable_group_5').addClass('course-settings-tier');
	};

	initSettings();

</script>

<?php

echo $OUTPUT->footer();
