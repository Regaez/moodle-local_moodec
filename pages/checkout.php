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
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('checkout_title', 'local_moodec'));
$PAGE->set_heading(get_string('checkout_title', 'local_moodec'));

// Get the renderer for this page
$renderer = $PAGE->get_renderer('local_moodec');

require_login();
// require_capability('local/moodec:checkout', $systemcontext);

$removedProducts = (array) json_decode(optional_param('enrolled', '', PARAM_TEXT));

// Get the cart in it's current state
$cart = local_moodec_get_cart();

$removed = array();

// If your cart is empty, redirect to cart page.
if (!is_array($cart['courses']) || 0 === count($cart['courses'])) {
	redirect(new moodle_url('/local/moodec/pages/cart.php'));
}

foreach ($cart['courses'] as $product => $value) {
	$context = context_course::instance($product);
	$isEnrolled = is_enrolled($context, $USER, '', true);

	if ($isEnrolled) {
		local_moodec_cart_remove($product);
		$removed[] = $product;
	}
}

if (0 < count($removed)) {
	redirect(new moodle_url('/local/moodec/pages/checkout.php', array('enrolled' => json_encode($removed))));
}

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