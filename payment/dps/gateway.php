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

require_login();

$transactionID = required_param('transaction', PARAM_INT);  // plugin instance id

$gateway = new MoodecGatewayDPS($transactionID);

$response = $gateway->begin();

// abort if DPS returns an invalid response
if ($response->attributes()->valid != '1') {
    print_error('error_dpsinitiate', 'local_moodec');
} else {
	// otherwise, redirect to the DPS provided URI
	redirect($response->URI);
}