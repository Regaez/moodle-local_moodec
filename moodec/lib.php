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
 * Formats the course summary description
 *
 * @param  int 		$id 	the course id
 * @return string     		the formatted text
 */
function local_moodec_format_course_summary($id) {
	global $CFG;
	require_once $CFG->libdir . '/filelib.php';
	require_once $CFG->libdir . '/weblib.php';

	$course = get_course($id);

	if (strlen($course->summary) < 1) {
		return '';
	}

	$context = context_course::instance($course->id);

	$options = array(
		'para' => false,
		'newlines' => true,
		'overflowdiv' => false,
	);

	$summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null);
	return format_text($summary, $course->summaryformat, $options, $course->id);
}

/**
 * Formats the enrolment duration to
 * @param  [type] $duration [description]
 * @return [type]           [description]
 */
function local_moodec_format_enrolment_duration($duration) {
	$output = '';

	if (364 < $duration) {
		$years = floor($duration / 365);
		$duration = $duration % 365;
		$output .= $years == 1 ? $years . ' year ' : $years . ' years ';
	}

	if (30 < $duration) {
		$months = floor($duration / 30);
		$duration = $duration % 30;
		$output .= $months == 1 ? $months . ' month ' : $months . ' months ';
	}

	if (7 < $duration) {
		$weeks = floor($duration / 7);
		$duration = $duration % 7;
		$output .= $weeks == 1 ? $weeks . ' week ' : $weeks . ' weeks ';
	}

	if (0 < $duration) {
		$output .= $duration == 1 ? $duration . ' day ' : $duration . ' days';
	}

	return $output;
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

/**
 * Returns the cart from cookie
 * @return array cart
 */
function local_moodec_get_cart() {
	global $DB;

	if (isset($_COOKIE['moodec_cart'])) {

		$storedCart = local_moodec_object_to_array(json_decode($_COOKIE['moodec_cart']));
		$validCart = $storedCart;

		// Check all the products which exist
		foreach ($storedCart['courses'] as $product => $quantity) {

			$productExists = $DB->get_record('local_moodec_course', array('courseid' => $product));

			if (!!$productExists) {
				if (!$productExists->show_in_store) {
					$updateCart = true;
					// If it shouldn't be shown in store, remove
					unset($validCart['courses'][$product]);
				}
			} else {
				$updateCart = true;
				// If there is no longer an entry in the DB, remove
				unset($validCart['courses'][$product]);
			}
		}

		// Returns only valid products
		return $validCart;
	}

	return false;
}

/**
 * Adds an item to the cart
 * @param  int 	$id		course id
 * @return array    	the cart
 */
function local_moodec_cart_add($id) {
	$newCart = array();
	$id = (int) $id;

	$cart = local_moodec_get_cart();

	if (!!$cart) {
		$newCart = $cart;
	}

	$newCart['courses'][$id] = 1;

	setcookie('moodec_cart', json_encode($newCart), time() + 31536000);

	return $newCart;
}

function local_moodec_cart_remove($id) {
	$newCart = array();
	$id = (int) $id;

	$cart = local_moodec_get_cart();

	if (!!$cart) {
		$newCart = $cart;
	}

	unset($newCart['courses'][$id]);

	setcookie('moodec_cart', json_encode($newCart), time() + 31536000);

	return $newCart;
}

/**
 * Converts an object (and any nested objects) to an array
 * @param  stdClass $obj 	an object or class
 * @return array      		the converted object as an array
 */
function local_moodec_object_to_array($obj) {
	if (is_object($obj)) {
		$obj = (array) $obj;
	}

	if (is_array($obj)) {
		$new = array();
		foreach ($obj as $key => $val) {
			$new[$key] = local_moodec_object_to_array($val);
		}
	} else {
		$new = $obj;
	}

	return $new;
}

function local_moodec_cart_get_total() {
	global $DB;
	$sum = 0;
	$cart = local_moodec_get_cart();

	if (!!$cart) {
		foreach ($cart['courses'] as $product => $value) {
			$moodecCourse = $DB->get_record('local_moodec_course', array('courseid' => $product));
			$sum += (float) $moodecCourse->price;
		}
	}

	return number_format($sum, 2, '.', ',');
}

function local_moodec_get_currencies() {
	// See https://www.paypal.com/cgi-bin/webscr?cmd=p/sell/mc/mc_intro-outside,
	// 3-character ISO-4217: https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_currency_codes
	$codes = array(
		'AUD', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'ILS', 'JPY',
		'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RUB', 'SEK', 'SGD', 'THB', 'TRY', 'TWD', 'USD');
	$currencies = array();
	foreach ($codes as $c) {
		$currencies[$c] = new lang_string($c, 'core_currencies');
	}

	return $currencies;
}