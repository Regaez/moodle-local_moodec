<?php
/**
 * Moodec DPS Gateway
 *
 * @package     local
 * @subpackage  local_moodec
 * @author      Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(__FILE__) . '/../../../../config.php';
require_once $CFG->dirroot . '/local/moodec/lib.php';

// var_dump($_POST['id']);

// $transactionID = required_param('id', PARAM_INT);  // plugin instance id
$transactionID = (int) $_POST['id'];
// Set PAGE variables
// $PAGE->set_context(context_system::instance());
// $PAGE->set_url(new moodle_url('/local/moodec/payment/dps/', array('id' => $transactionID)));

//require_login();

$gateway = new MoodecGatewayDPS($transactionID);

// echo "<pre>";

$response = $gateway->begin();

// var_dump($response);

// echo "</pre>";
// exit;

// abort if DPS returns an invalid response
if ($response->attributes()->valid != '1') {
    print_error('error_dpsinitiate', 'local_moodec');
} else {
	// otherwise, redirect to the DPS provided URI
	redirect($response->URI);
}