<?php
/**
 * Moodec DPS Payment Success 
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

// This is the result of the transaction from DPS
$data = required_param('result', PARAM_CLEAN);

// We instantiate the cart in order to get the transaction id
$cart = new MoodecCart(); 

// Then we can set up the gateway using the cart transaction id
$gateway = new MoodecGatewayDPS($cart->get_transaction_id());

// Now handle the data from DPS
$gateway->abort($data);

redirect(new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php')); // PERHAPS MAKE CONFIGURABLE?