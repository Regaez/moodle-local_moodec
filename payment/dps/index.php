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

$transactionID = required_param('id', PARAM_INT);  // plugin instance id

require_login();

$transaction = new MoodecTransaction($transactionID);

// If the transaction is already completed, we do not want to do it again
if( $transaction->get_status() === MoodecTransaction::STATUS_COMPLETE ) {
	redirect(new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php'));
}

$gateway = new MoodecGatewayDPS($transaction);

$response = $gateway->begin();

// abort if DPS returns an invalid response
if ($response->attributes()->valid != '1') {
    print_error('error_dpsinitiate', 'local_moodec');
} else {
	// otherwise, redirect to the DPS provided URI
	redirect($response->URI);
}