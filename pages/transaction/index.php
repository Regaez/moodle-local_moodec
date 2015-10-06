<?php 
/**
 * Moodec Transactions Page
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(__FILE__) . '/../../../../config.php';
require_once $CFG->dirroot . '/local/moodec/lib.php';
require_once $CFG->dirroot . '/lib/tablelib.php';
require_once $CFG->dirroot . '/local/moodec/classes/transaction_table.php';

$params = array();

// Get the ID of the user to be displayed
$userID = optional_param('user', 0, PARAM_INT);
// The following are filters for the table
$dateFrom = optional_param('date-from', null, PARAM_RAW);
$dateTo = optional_param('date-to', null, PARAM_RAW);
$gatewayPaypal = optional_param('paypal', 0, PARAM_RAW);
$gatewayDPS = optional_param('dps', 0, PARAM_RAW);
$statusComplete = optional_param('status-complete', 0, PARAM_RAW);
$statusFailed = optional_param('status-failed', 0, PARAM_RAW);
$statusPending = optional_param('status-pending', 0, PARAM_RAW);
$statusNoSubmit = optional_param('status-nosubmit', 0, PARAM_RAW);
// Determines whether or not to download the table
$download = optional_param('download', '', PARAM_ALPHA);

if( !!$userID && is_int($userID) ) {
	$params['user'] = $userID;
}

if( !!$dateFrom && !!strtotime($dateFrom) ) {
	$params['date-from'] = $dateFrom;
}

if( !!$dateTo && !!strtotime($dateTo)) {
	$params['date-to'] = $dateTo;
}

if( !!$gatewayPaypal || $gatewayPaypal === 'on') {
	$params['paypal'] = 1;
}

if( !!$gatewayDPS || $gatewayDPS === 'on' ) {
	$params['dps'] = 1;
}

if( !!$statusComplete || $statusComplete === 'on' ) {
	$params['status-complete'] = 1;
}

if( !!$statusFailed || $statusFailed === 'on' ) {
	$params['status-failed'] = 1;
}

if( !!$statusPending || $statusPending === 'on' ) {
	$params['status-pending'] = 1;
}

if( !!$statusNoSubmit || $statusNoSubmit === 'on' ) {
	$params['status-nosubmit'] = 1;
}

$context = context_system::instance();

// Set PAGE variables
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/moodec/pages/transaction/index.php', $params);

// Force the user to login/create an account to access this page
require_login();

if ( !has_capability('local/moodec:viewalltransactions', $context) ) {
	$userID = $USER->id;
}

// Check if the theme has a moodec pagelayout defined, otherwise use standard
if (array_key_exists('moodec_transactions', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec_transactions');
} else if(array_key_exists('moodec', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec');
} else {
	$PAGE->set_pagelayout('standard');
}

// Get the renderer for this page
$renderer = $PAGE->get_renderer('local_moodec');

$table = new moodec_transaction_table('moodec-transactions-list');
$table->is_downloading($download, 'transaction-report', 'Moodec Transaction Report');

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data
    // Print the page header
	$PAGE->set_title(get_string('transactions_title', 'local_moodec'));
	$PAGE->set_heading(get_string('transactions_title', 'local_moodec'));

    echo $OUTPUT->header();

    printf('<h1 class="page__title">%s</h1>', get_string('transactions_title', 'local_moodec'));

    $url = new moodle_url($CFG->wwwroot . '/local/moodec/pages/transaction/index.php');
    echo $renderer->transaction_filter($params, $url);
}

// Configure the table
$table->define_baseurl(new moodle_url($CFG->wwwroot . '/local/moodec/pages/transaction/index.php', $params));

$table->set_attribute('class', 'admintable generaltable transaction_table');
$table->collapsible(false);

$table->is_downloadable(true);
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));

// Initialise variables used to build the query
$query = '1';
$paramCount = count($params);
$iterator = 1;

if( 0 < $paramCount ) {
	$query .= ' AND ';
}

// Work out the sql where clause for the table based on filter params
foreach($params as $key => $value) {
	$addOr = false;

	switch($key) {
		case 'user':
			$query .= sprintf('user_id = %d', $value);
			break;

		case 'date-from':
			$query .= sprintf('purchase_date > %s', strtotime($value));
			break;

		case 'date-to':
			$query .= sprintf('purchase_date < %s', strtotime($value));
			break;

		case 'paypal':
			$addOr = isset($params['dps']);
			$query .= !!$addOr ? '(' : ''; 
			$query .= sprintf('gateway = "%s"', MOODEC_GATEWAY_PAYPAL);
			break;

		case 'dps':
			$query .= sprintf('gateway = "%s"', MOODEC_GATEWAY_DPS);
			$query .= isset($params['paypal']) ? ')' : '';
			break;

		case 'status-complete':
			$addOr = isset($params['status-failed']) || isset($params['status-pending']) || isset($params['status-nosubmit']);
			$query .= !!$addOr ? '(' : ''; 
			$query .= sprintf('status = %d', MoodecTransaction::STATUS_COMPLETE);
			break;

		case 'status-failed':
			$addOr = isset($params['status-pending']) || isset($params['status-nosubmit']);
			$query .= (!isset($params['status-complete']) && !!$addOr ) ? '(' : ''; 
			$query .= sprintf('status = %d', MoodecTransaction::STATUS_FAILED);
			$query .= (isset($params['status-complete']) && !isset($params['status-pending']) && !isset($params['status-nosubmit'])) ? ')' : '';
			break;

		case 'status-pending':
			$addOr = isset($params['status-nosubmit']);
			$query .= (!isset($params['status-complete']) && !isset($params['status-failed']) && !!$addOr ) ? '(' : ''; 
			$query .= sprintf('status = %d', MoodecTransaction::STATUS_PENDING);
			$query .= ( (isset($params['status-complete']) || isset($params['status-failed'])) && !isset($params['status-nosubmit'])) ? ')' : '';
			break;

		case 'status-nosubmit':
			$query .= sprintf('status = %d', MoodecTransaction::STATUS_NOT_SUBMITTED);
			$query .= (isset($params['status-complete']) || isset($params['status-failed']) || isset($params['status-pending'])) ? ')' : '';
			break;
	}

	if( !!$addOr && $iterator < $paramCount) {
		$query .= ' OR ';
	} elseif( !$addOr && $iterator < $paramCount) {
		$query .= ' AND ';
	}

	$iterator++;
}

$table->set_sql('*', "{local_moodec_transaction}", $query);


$table->out(50, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}