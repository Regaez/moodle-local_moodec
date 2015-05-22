<?php
/**
 * Moodec Listener for Instant Paypment Notification from Paypal
 *
 * @package     local
 * @subpackage  local_moodec
 * @author      Thomas Threadgold - based on code by others (Paypal Enrolment plugin)
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', true);

require "../../config.php";
require_once "lib.php";
require_once $CFG->libdir . '/eventslib.php';
require_once $CFG->libdir . '/enrollib.php';
require_once $CFG->libdir . '/filelib.php';
require_once $CFG->dirroot . '/group/lib.php';

// PayPal does not like when we return error messages here,
// the custom handler just logs exceptions and stops.
set_exception_handler('local_moodec_ipn_exception_handler');


/// Keep out casual intruders
if (empty($_POST) or !empty($_GET)) {
	print_error("Sorry, you can not use the script that way.");
}

/// Read all the data from PayPal and get it ready for later;
/// we expect only valid UTF-8 encoding, it is the responsibility
/// of user to set it up properly in PayPal business account,
/// it is documented in docs wiki.

$req = 'cmd=_notify-validate';

$data = new stdClass();

foreach ($_POST as $key => $value) {
	$req .= "&$key=" . urlencode($value);
	$data->$key = $value;
}

$courseList = explode('|', $data->custom);

$data->userid = (int) str_replace('U:', '', array_shift($courseList));
$data->payment_gross = $data->mc_gross;
$data->payment_currency = $data->mc_currency;
$data->timeupdated = time();

/// get the user and course records

if (!$user = $DB->get_record("user", array("id" => $data->userid))) {
	message_paypal_error_to_admin("Not a valid user id", $data);
	die;
}

$newList = array();

foreach ($courseList as $c) {
	$c = explode(',', $c);
	$courseid = (int) str_replace('C:', '', $c[0]);
	$variationid = (int) str_replace('V:', '', $c[1]);

	if (!$course = $DB->get_record("course", array("id" => $courseid))) {
		message_paypal_error_to_admin("Not a valid course id", $data);
		die;
	}

	if (!$context = context_course::instance($course->id, IGNORE_MISSING)) {
		message_paypal_error_to_admin("Not a valid context id", $data);
		die;
	}

	array_push($newList, array('id' => $courseid, 'variation'=>$variationid));
}

$courseList = $newList;

$plugin = enrol_get_plugin('moodec');

$c = new curl();
$options = array(
	'returntransfer' => true,
	'httpheader' => array('application/x-www-form-urlencoded', "Host: www.paypal.com"),
	'timeout' => 30,
	'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
);
$location = "https://www.paypal.com/cgi-bin/webscr";
$result = $c->post($location, $req, $options);

echo "<pre>";
var_dump($req);
echo "</pre>";

if (!$result) {
	/// Could not connect to PayPal - FAIL
	echo "<p>Error: could not access paypal.com</p>";
	message_paypal_error_to_admin("Could not access paypal.com to verify payment", $data);
	die;
}

/// Connection is OK, so now we post the data to validate it

/// Now read the response and check if everything is OK.

if (strlen($result) > 0) {
	if (strcmp($result, "VERIFIED") == 0) {
		// VALID PAYMENT!

		// check the payment_status and payment_reason

		// If status is not completed or pending then unenrol the student if already enrolled
		// and notify admin

		if ($data->payment_status != "Completed" and $data->payment_status != "Pending") {

			foreach ($courseList as $c) {

				$instance = $DB->get_record('enrol', array('courseid' => $c['id'], 'enrol' => 'moodec'));

				$data->courseid = $c['id'];
				$data->instanceid = $instance->id;

				$plugin->unenrol_user($instance, $data->userid);
			}
			message_paypal_error_to_admin("Status not completed or pending. User unenrolled from course", $data);
			die;
		}

		// If currency is incorrectly set then someone maybe trying to cheat the system

		if ($data->mc_currency != get_config('local_moodec', 'currency')) {
			message_paypal_error_to_admin("Currency does not match course settings, received: " . $data->mc_currency, $data);
			die;
		}

		// If status is pending and reason is other than echeck then we are on hold until further notice
		// Email user to let them know. Email admin.

		if ($data->payment_status == "Pending" and $data->pending_reason != "echeck") {
			$eventdata = new stdClass();
			$eventdata->modulename = 'moodle';
			$eventdata->component = 'local_moodec';
			$eventdata->name = 'local_moodec_payment';
			$eventdata->userfrom = get_admin();
			$eventdata->userto = $user;
			$eventdata->subject = "Moodle: PayPal payment";
			$eventdata->fullmessage = "Your PayPal payment is pending.";
			$eventdata->fullmessageformat = FORMAT_PLAIN;
			$eventdata->fullmessagehtml = '';
			$eventdata->smallmessage = '';
			message_send($eventdata);

			message_paypal_error_to_admin("Payment pending", $data);
			die;
		}

		// If our status is not completed or not pending on an echeck clearance then ignore and die
		// This check is redundant at present but may be useful if paypal extend the return codes in the future

		if (!($data->payment_status == "Completed" or
			($data->payment_status == "Pending" and $data->pending_reason == "echeck"))) {
			die;
		}

		// At this point we only proceed with a status of completed or pending with a reason of echeck

		foreach ($courseList as $c) {

			if ($existing = $DB->get_record("local_moodec_paypal", array("txn_id" => $data->txn_id, 'courseid' => $c['id']))) {
				// Make sure this transaction doesn't exist already
				message_paypal_error_to_admin("Transaction $data->txn_id for course ".$c['id']." is being repeated!", $data);
				die;

			}
		}

		if (core_text::strtolower($data->business) !== core_text::strtolower(get_config('local_moodec', 'paypalbusiness'))) {
			// Check that the email is the one we want it to be
			message_paypal_error_to_admin("Business email is {$data->business} (not " .
				get_config('local_moodec', 'paypalbusiness') . ")", $data);
			die;

		}

		if (!$user = $DB->get_record('user', array('id' => $data->userid))) {
			// Check that user exists
			message_paypal_error_to_admin("User $data->userid doesn't exist", $data);
			die;
		}

		foreach ($courseList as $c) {
			if (!$course = $DB->get_record('course', array('id' => $c['id']))) {
				// Check that course exists
				message_paypal_error_to_admin("Course ".$c['id']." doesn't exist", $data);
				die;
			}
		}

		// get the sum of all the products in the paypal transaction
		$cost = 0;
		foreach ($courseList as $c) {
			$thisCourse = $DB->get_record('local_moodec_course', array('courseid' => $c['id']));
			if(	$c['variation'] === 0) {
				$cost += (float) $thisCourse->simple_price;
			} else {
				$price = "variable_price_".$c['variation'];
				$cost += (float) $thisCourse->$price;
			}
		}

		// Use the same rounding of floats as on the enrol form.
		$cost = format_float($cost, 2, false);

		if ($data->payment_gross < $cost) {
			message_paypal_error_to_admin("Amount paid is not enough ($data->payment_gross < $cost))", $data);
			die;

		}

		// ALL CLEAR !

		foreach ($courseList as $c) {
			$data->courseid = $c['id'];
			$data->variation = $c['variation'];
			$instance = $DB->get_record('enrol', array('courseid' => $c['id'], 'enrol' => 'moodec'));
			$timestart = time();
			$timeend = 0;

			$DB->insert_record("local_moodec_paypal", $data);

			$thisCourse = $DB->get_record('local_moodec_course', array('courseid' => $c['id']));

			if( $c['variation'] === 0 ) {
				if (!!$thisCourse->simple_enrolment_duration) {
					$timeend = $timestart + ((int)$thisCourse->simple_enrolment_duration * 86400);
				}
			} else {
				$duration = "variable_enrolment_duration_".$c['variation'];
				if (!!$thisCourse->$duration) {
					$timeend = $timestart + ((int)$thisCourse->$duration * 86400);
				}
			}

			$plugin->enrol_user($instance, $user->id, $instance->roleid, $timestart, $timeend);

			// Now check if they should be added to a group
			if( $c['variation'] === 0 ) {
				$group = "simple_group";
			} else {
				$group = "variable_group_" . $c['variation'];
			}

			// if there is a group set (ie NOT 0), then add them to it
			if (!!$thisCourse->$group) { 
				$result = groups_add_member($thisCourse->$group, $user->id);
			}	
		}

	} else if (strcmp($result, "INVALID") == 0) {
		// ERROR
		$DB->insert_record("local_moodec_paypal", $data, false);
		message_paypal_error_to_admin("Received an invalid payment notification!! (Fake payment?)", $data);
	}
}

exit;

//--- HELPER FUNCTIONS --------------------------------------------------------------------------------------

function message_paypal_error_to_admin($subject, $data) {
	echo $subject;
	$admin = get_admin();
	$site = get_site();

	$message = "$site->fullname:  Transaction failed.\n\n$subject\n\n";

	foreach ($data as $key => $value) {
		$message .= "$key => $value\n";
	}

	$eventdata = new stdClass();
	$eventdata->modulename = 'moodle';
	$eventdata->component = 'local_moodec';
	$eventdata->name = 'local_moodec_payment';
	$eventdata->userfrom = $admin;
	$eventdata->userto = $admin;
	$eventdata->subject = "PAYPAL ERROR: " . $subject;
	$eventdata->fullmessage = $message;
	$eventdata->fullmessageformat = FORMAT_PLAIN;
	$eventdata->fullmessagehtml = '';
	$eventdata->smallmessage = '';
	message_send($eventdata);
}

/**
 * Silent exception handler.
 *
 * @param Exception $ex
 * @return void - does not return. Terminates execution!
 */
function local_moodec_ipn_exception_handler($ex) {
	$info = get_exception_info($ex);

	$logerrmsg = "local_moodec IPN exception handler: " . $info->message;
	if (debugging('', DEBUG_NORMAL)) {
		$logerrmsg .= ' Debug: ' . $info->debuginfo . "\n" . format_backtrace($info->backtrace, true);
	}
	error_log($logerrmsg);

	exit(0);
}