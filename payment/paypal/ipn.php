<?php
/**
 * Moodec Listener for Instant Payment Notification from Paypal
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

require_once "../../../../config.php";
require_once $CFG->dirroot . '/local/moodec/lib.php';

/// Keep out casual intruders
if (empty($_POST) or !empty($_GET)) {
	print_error("Sorry, you can not use the script that way.");
}

$plugin = enrol_get_plugin('moodec');

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

// GET TRANSACTION FROM DATA
$transaction = new MoodecTransaction((int) $data->custom);


// CONFIRM NOTIFICATION WITH PAYPAL
$c = new curl();
$options = array(
	'returntransfer' => true,
	'httpheader' => array('application/x-www-form-urlencoded', "Host: www.paypal.com"),
	'timeout' => 30,
	'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
);
$location = "https://www.paypal.com/cgi-bin/webscr";
$result = $c->post($location, $req, $options);


// CHECK TRANSACTION CURRENT STATUS
if( $transaction->get_status() === MoodecTransaction::STATUS_COMPLETE ) {
	// this transaction has already been marked as complete, so we don't want to go
	// through the process again
	die;
}

// Read the response from Paypal and validate the data one more time
// to make sure everything is OK.
if (strlen($result) > 0) {
	if (strcmp($result, "VERIFIED") == 0) {

		// If we are here, it means the payment was a valid paypal transaction
		// So now we check the payment status and transaction details to ensure they match
		// the Moodle courses/users etc

		// If status is not completed or pending then unenrol the student if already enrolled
		// and notify admin
		if ($data->payment_status != "Completed" and $data->payment_status != "Pending") {

			foreach ($transaction->get_items() as $item) {

				$product = local_moodec_get_product($item->get_product_id());

				$instance = $DB->get_record('enrol', array('courseid' => $product->get_course_id(), 'enrol' => 'moodec'));

				$data->courseid = $product->get_course_id();
				$data->instanceid = $instance->id;

				$plugin->unenrol_user($instance, $transaction->get_user_id() );
			}

			message_paypal_error_to_admin("Status not completed or pending. User unenrolled from course", $data);

			$transaction->fail();

			die;
		}

		// Confirm currency is correctly set and matches the plugin config
		if ($data->mc_currency != get_config('local_moodec', 'currency')) {
			message_paypal_error_to_admin("Currency does not match course settings, received: " . $data->mc_currency, $data);

			$transaction->fail();

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

		// --------------------
		// At this point we only proceed with a status of completed or pending with a reason of echeck
		// --------------------

		// The email address paid to does not match the one we expect
		if (core_text::strtolower($data->business) !== core_text::strtolower(get_config('local_moodec', 'payment_paypal_email'))) {
			// Check that the email is the one we want it to be
			message_paypal_error_to_admin("Paypal business email is {$data->business} (not " .
				get_config('local_moodec', 'payment_paypal_email') . ")", $data);
			
			$transaction->fail();
			die;

		}

		// Ensure that the user associated to the transaction actually exists
		if (!$user = $DB->get_record('user', array('id' => $transaction->get_user_id())) ) {
			// Check that user exists
			message_paypal_error_to_admin("User ".$transaction->get_user_id()." doesn't exist", $data);
			
			$transaction->fail();
			die;
		}


		// Check that each course associated with the items in the transaction ACTUALLY exist
		// (it should be VERY unlikely for this to happen, but we check nonetheless!)
		foreach ($transaction->get_items() as $item) {
			
			$product = local_moodec_get_product($item->get_product_id());

			if (!$course = $DB->get_record('course', array('id' => $product->get_course_id()))) {
				// Check that course exists
				message_paypal_error_to_admin("Course ".$product->get_course_id()." doesn't exist", $data);
				
				$transaction->fail();
				die;
			}
		}

		// Check if the payment was less than the transaction cost
		if( $data->payment_gross < $transaction->get_cost() ) {
			
			message_paypal_error_to_admin("Amount paid is not enough (".$data->payment_gross." < ".$transaction->get_cost().")", $data);

			$transaction->fail();
			die;
		}

		// ALL CLEAR!

		// ENROL USER INTO TRANSACTION ITEMS
		foreach ($transaction->get_items() as $item) {

			$product = local_moodec_get_product($item->get_product_id());

			$timestart = time();
			$timeend = 0;
			$instance = $DB->get_record('enrol', array('courseid' => $product->get_course_id(), 'enrol' => 'moodec'));

			// Check if the product is simple, or variable
			if( $product->get_type() === PRODUCT_TYPE_SIMPLE ) {
				$timeend = $timestart + ( $product->get_duration() * 86400 );
			} else {
				$timeend = $timestart + ( $product->get_variations($item->get_variation_id())->get_duration() * 86400 );
			}

			// This will enrol the user! yay!
			$plugin->enrol_user($instance, $user->id, $instance->roleid, $timestart, $timeend);


			// if there is a group set (ie NOT 0), then add them to it
			if ( !!$product->get_group_id() ) { 
				$result = groups_add_member($product->get_group_id(), $transaction->get_user_id() );
			}	

		}

		// Mark the transaction as complete! :D
		$transaction->complete();
	}
}

exit;

// -------------------------------------------------------------------------------------
// 		HELPER FUNCTION 
// -------------------------------------------------------------------------------------

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