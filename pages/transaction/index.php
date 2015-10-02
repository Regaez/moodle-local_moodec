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

if( !!$userID ) {
	$params['user'] = $userID;
}

// Set PAGE variables
$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot . '/local/moodec/pages/transaction/index.php', $params);
$PAGE->navbar->add(get_string('transactions_title', 'local_moodec'), new moodle_url($CFG->wwwroot . '/local/moodec/pages/transaction/index.php'));

$download = optional_param('download', '', PARAM_ALPHA);

// Force the user to login/create an account to access this page
require_login();

// Check if the theme has a moodec pagelayout defined, otherwise use standard
if (array_key_exists('moodec_transactions', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec_transactions');
} else if(array_key_exists('moodec', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec');
} else {
	$PAGE->set_pagelayout('standard');
}

$table = new moodec_transaction_table('moodec-transactions-list');
$table->is_downloading($download, 'transaction-report', 'Moodec Transaction Report');

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data
    // Print the page header
	$PAGE->set_title(get_string('transactions_title', 'local_moodec'));
	$PAGE->set_heading(get_string('transactions_title', 'local_moodec'));

    echo $OUTPUT->header();

    printf('<h1 class="page__title">%s</h1>', get_string('transactions_title', 'local_moodec'));

}

$table->define_baseurl(new moodle_url($CFG->wwwroot . '/local/moodec/pages/transaction/index.php'));

$table->set_attribute('class', 'admintable generaltable');
$table->collapsible(false);

$table->is_downloadable(true);
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));

// Work out the sql for the table.
if( !!$userID ) {
	$table->set_sql('*', "{local_moodec_transaction}", 'user_id = ' . $userID);
} else {
	$table->set_sql('*', "{local_moodec_transaction}", '1');
}

$table->out(100, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}