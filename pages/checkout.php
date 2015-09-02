<?php
/**
 *Moodec Checkout Page
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(__FILE__) . '/../../../config.php';
require_once $CFG->dirroot . '/local/moodec/lib.php';

$systemcontext = context_system::instance();

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/moodec/pages/checkout.php');

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

// Get the renderer for this page
$renderer = $PAGE->get_renderer('local_moodec');

require_login();
// require_capability('local/moodec:checkout', $systemcontext);

$removedProducts = (array) json_decode(optional_param('enrolled', '', PARAM_TEXT));

// Get the cart in it's current state
$cart = new MoodecCart();

// If your cart is empty, redirect to cart page.
if ( $cart->is_empty() ) {
	redirect(new moodle_url('/local/moodec/pages/cart.php'));
}

$removedProducts = $cart->refresh();

echo $OUTPUT->header(); ?>

<h1 class="page__title"><?php echo get_string('checkout_title', 'local_moodec'); ?></h1>

<?php

if (isguestuser()) {

	$SESSION->wantsurl = new moodle_url('/local/moodec/pages/checkout.php');

	printf(
		'<p>%s</p>',
		get_string('checkout_guest_message', 'local_moodec')
	);

	printf(
		'<form method="GET" action="%s"><input type="hidden" name="sesskey" value="%s"><input type="submit" value="%s"></form>',
		new moodle_url('/login'),
		$USER->sesskey,
		get_string('button_logout_label', 'local_moodec')
	);

} else {

	// Output the checkout cart
	echo $renderer->moodec_cart($cart, true, $removedProducts);

}

echo $OUTPUT->footer();