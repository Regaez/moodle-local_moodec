<?php
/**
 * Moodec Single Transaction Page
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(__FILE__) . '/../../../../config.php';
require_once $CFG->dirroot . '/local/moodec/lib.php';

$params = array();

// Get the ID of the user to be displayed
$transactionID = optional_param('id', 0, PARAM_INT);

if( !!$transactionID ) {
	$params['id'] = $transactionID;
}

$context = context_system::instance();

// Set PAGE variables
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/moodec/pages/transaction/view.php', $params);

$PAGE->navbar->add(get_string('transactions_title', 'local_moodec'), new moodle_url($CFG->wwwroot . '/local/moodec/pages/transaction/index.php'));
$PAGE->navbar->add(get_string('transaction_view_title', 'local_moodec', array('id'=>$transactionID)));

// Force the user to login/create an account to access this page
require_login();

$transaction = new MoodecTransaction($transactionID);

if ( (int) $USER->id !== $transaction->get_user_id() ) {
	require_capability('local/moodec:viewalltransactions', $context);
}

// Check if the theme has a moodec pagelayout defined, otherwise use standard
if (array_key_exists('moodec_transaction_view', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec_transaction_view');
} else if(array_key_exists('moodec', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec');
} else {
	$PAGE->set_pagelayout('standard');
}

// Get the renderer for this page
$renderer = $PAGE->get_renderer('local_moodec');

//needs to have the product verified before setting page heading & title
$PAGE->set_title(get_string('transaction_view_title', 'local_moodec', array('id'=>$transactionID)));
$PAGE->set_heading(get_string('transaction_view_title', 'local_moodec', array('id'=>$transactionID)));

echo $OUTPUT->header();

?>

<h1 class="page__title"><?php echo get_string('transaction_view_title', 'local_moodec', array('id'=>$transactionID)); ?></h1>

<?php 

// Render the transaction list
echo $renderer->single_transaction($transaction);

if( !!$transaction->get_error() && has_capability('local/moodec:viewalltransactions', $context)) {
	printf(
		'<div class="span12 desktop-first-column">
			<h4>%s</h4>
			<pre>%s</pre>
		</div>',
		get_string('transaction_section_error', 'local_moodec'),
		$transaction->get_error()
	);
}

echo $OUTPUT->footer();