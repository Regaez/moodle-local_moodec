<?php
/**
 * Moodec Checkout
 *
 * @package     local
 * @subpackage  local_moodec
 * @author      Thomas Threadgold - based on code by others (Paypal Enrolment plugin)
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(__FILE__) . '/../../../config.php';
require_once $CFG->dirroot . '/local/moodec/lib.php';

$systemcontext = context_system::instance();

$PAGE->set_context($systemcontext);
$PAGE->set_url($CFG->wwwroot . '/local/moodec/pages/checkout.php');

// Check if the theme has a moodec pagelayout defined, otherwise use standard
if (array_key_exists('moodec_checkout', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec_checkout');
} else if(array_key_exists('moodec', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec');
} else {
	$PAGE->set_pagelayout('standard');
}

$PAGE->set_title(get_string('checkout_title', 'local_moodec'));
$PAGE->set_heading(get_string('checkout_title', 'local_moodec'));

$PAGE->navbar->add(get_string('cart_title', 'local_moodec'), new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php'));
$PAGE->navbar->add(get_string('checkout_title', 'local_moodec'));


// Get the renderer for this page
$renderer = $PAGE->get_renderer('local_moodec');

// Force the user to login/create an account to access this page
require_login();

// Get the cart
$cart = new MoodecCart();

if ( $cart->is_empty() ) {
	redirect(new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php'));
}

// Check if the products in the cart are valid, store the ones that are not
// (so we can notify the user they've been removed)
$removedProducts = $cart->refresh();

// Check if a transaction has already been made for this cart
if( !!$cart->get_transaction_id() ) {

	// Get the existing transaction if the cart has recorded one
	$transaction = new MoodecTransaction($cart->get_transaction_id());
	// We reset the transaction, in case the items in the cart have changed 
	$transaction->reset();

} else {

	// Otherwise create a new transaction
	$transaction = new MoodecTransaction();

}

// Set the transactionId in the cart to that of the transaction
// We do this in case the transaction reset created a new transaction
// Or, to add the transaction id to the cart if one didn't already exist
$cart->set_transaction_id($transaction->get_id());

// We need to add all the products in the cart to the transaction
foreach( $cart->get() as $pID => $vID ) {

	// Get the product in the cart
	$product = local_moodec_get_product($pID);

	// Add the product to the transaction, relative to variation
	if( $product->get_type() === PRODUCT_TYPE_VARIABLE ) {
		$transaction->add($pID, $product->get_variation($vID)->get_price(), $vID);
	} else {
		$transaction->add($pID, $product->get_price());
	}
}

echo $OUTPUT->header(); ?>

<h1 class="page__title"><?php echo get_string('checkout_title', 'local_moodec'); ?></h1>

<?php // Output cart review
echo $renderer->cart_review($cart, $removedProducts);

echo $OUTPUT->footer();