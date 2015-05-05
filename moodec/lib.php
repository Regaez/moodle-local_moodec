<?php
/**
 * Moodec Library file
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWords Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function local_moodec_extends_navigation(global_navigation $nav) {
	global $PAGE, $DB;

	// Add store container to menu
	$storenode = $PAGE->navigation->add('Store', new moodle_url('/local/moodec/pages/catalogue.php'), navigation_node::TYPE_CONTAINER);
	$products = $DB->get_records('local_moodec_course', array('show_in_store' => 1));

	// Add products to the store menu
	foreach ($products as $product) {
		$theCourse = get_course($product->courseid);
		$storenode->add($theCourse->fullname, new moodle_url('/local/moodec/pages/product.php', array('id' => $product->courseid)));
	}

	// Add cart page to menu
	$PAGE->navigation->add('Cart', new moodle_url('/local/moodec/pages/cart.php'));
}

/**
 * Display the Moodec settings in the course settings block
 * For 2.3 and onwards
 *
 * @param  settings_navigation $nav     The settings navigatin object
 * @param  stdclass            $context Course context
 */
function local_moodec_extends_settings_navigation(settings_navigation $nav, $context) {
	if ($context->contextlevel >= CONTEXT_COURSE and ($branch = $nav->get('courseadmin'))
		and has_capability('moodle/course:update', $context)) {
		$ltiurl = new moodle_url('/local/moodec/settings/course.php', array('id' => $context->instanceid));
		$branch->add(get_string('pluginname', 'local_moodec'), $ltiurl, $nav::TYPE_CONTAINER, null, 'moodec' . $context->instanceid);
	}
}

/**
 * Returns the url of the first image contained in the course summary file area
 * @param  int $id the course id
 * @return string     the url to the image
 */
function local_moodec_get_course_image_url($id) {
	global $CFG;
	require_once $CFG->libdir . "/filelib.php";
	$course = get_course($id);

	if ($course instanceof stdClass) {
		require_once $CFG->libdir . '/coursecatlib.php';
		$course = new course_in_list($course);
	}

	foreach ($course->get_course_overviewfiles() as $file) {
		$isimage = $file->is_valid_image();

		if ($isimage) {
			return file_encode_url("$CFG->wwwroot/pluginfile.php",
				'/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
				$file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
		}
	}

	return false;
}