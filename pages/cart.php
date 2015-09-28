<?php
/**
 *Moodec Cart Page
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
$PAGE->set_url($CFG->wwwroot . '/local/moodec/pages/cart.php');

// Check if the theme has a moodec pagelayout defined, otherwise use standard
if (array_key_exists('moodec_cart', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec_cart');
} else if(array_key_exists('moodec', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec');
} else {
	$PAGE->set_pagelayout('standard');
}

$PAGE->set_title(get_string('cart_title', 'local_moodec'));
$PAGE->set_heading(get_string('cart_title', 'local_moodec'));

// Get the renderer for this page
$renderer = $PAGE->get_renderer('local_moodec');

//
// Check for new items and add to cart
//

// Get the cart in it's current state
$cart = new MoodecCart();

// If we are adding to the cart, process this first
if (isset($_POST['action']) && $_POST['action'] === 'addToCart') {
	// Updates the cart var with the new addition
	$cart->add($_POST['id']);
	// redirect back to the course page
	redirect(new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php'));
}

// If we are adding to the cart, process this first
if (isset($_POST['action']) && $_POST['action'] === 'addVariationToCart') {
	// Updates the cart var with the new addition
	$cart->add($_POST['id'], $_POST['variation']);
	// redirect back to the course page
	redirect(new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php'));
}

if (isset($_POST['action']) && $_POST['action'] === 'removeFromCart') {
	// Updates the cart var with the new addition
	$cart->remove($_POST['id']);
	// redirect back to the course page
	redirect(new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php'));
}

if (isset($_POST['action']) && $_POST['action'] === 'emptyCart') {
	// Updates the cart var with the new addition
	$cart->clear();
	// redirect back to the course page
	redirect(new moodle_url($CFG->wwwroot . '/local/moodec/pages/catalogue.php'));
}

echo $OUTPUT->header();

// Render page title
printf('<h1 class="page__title">%s</h1>', get_string('cart_title', 'local_moodec'));

// Render the product page content
echo $renderer->moodec_cart($cart);


$relatedOutput = '';
// Check if there are any related products for each product in the cart
foreach($cart->get() as $id => $variation) {
	
	// Get the product for the given ID
	$product = local_moodec_get_product($id);
		
	// Get the HTML output of the related products renderer
	$relatedOutput = $renderer->related_products($product);

	// If there is any output, then we are happy and can continue!
	if( $relatedOutput !== '') {
		break;
	}
}

echo $relatedOutput;


echo $OUTPUT->footer();
