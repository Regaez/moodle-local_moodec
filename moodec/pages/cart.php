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
$PAGE->set_url('/local/moodec/pages/cart.php');
$PAGE->set_pagelayout('standard');

//
// TODO: Check for new items and add to cart
//

// Get the cart in it's current state
$cart = local_moodec_get_cart();

// If we are adding to the cart, process this first
if (isset($_POST['action']) && $_POST['action'] === 'addToCart') {
	// Updates the cart var with the new addition
	$cart = local_moodec_cart_add($_POST['id']);
	// redirect back to the course page
	redirect(new moodle_url('/local/moodec/pages/cart.php'));
}


// If we are adding to the cart, process this first
if (isset($_POST['action']) && $_POST['action'] === 'addVariationToCart') {
	// Updates the cart var with the new addition
	$cart = local_moodec_cart_add($_POST['id'], $_POST['variation']);
	// redirect back to the course page
	redirect(new moodle_url('/local/moodec/pages/cart.php'));
}

if (isset($_POST['action']) && $_POST['action'] === 'removeFromCart') {
	// Updates the cart var with the new addition
	$cart = local_moodec_cart_remove($_POST['id']);
	// redirect back to the course page
	redirect(new moodle_url('/local/moodec/pages/cart.php'));
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('cart_title', 'local_moodec'));

?>


<div class="cart-overview">

	<?php if (is_array($cart['courses']) && 0 < count($cart['courses'])) {
	?>

	<ul class="products">

	<?php foreach ($cart['courses'] as $courseid => $variation) {

		$product = local_moodec_get_product($courseid);?>

		<li class="product-item">
			<h4 class="product-title"><?php 
				if($variation === 0) {
					echo $product->fullname;
				} else {
					printf(
						'%s - %s',
					 	$product->fullname,
					 	$product->variations[$variation]->name
					); 
				}
			?></h4>
			<div class="product-price"><?php 
				if($variation === 0) {
					echo local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')) . $product->price;
				} else {
					echo local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')) .$product->variations[$variation]->price;
				}
			?></div>
			<form class="product__form" action="" method="POST">
				<input type="hidden" name="id" value="<?php echo $courseid;?>">
				<input type="hidden" name="action" value="removeFromCart">
				<input class="form__submit" type="submit" value="<?php echo get_string('button_remove_label', 'local_moodec');?>">
			</form>
		</li>

	<?php }?>

	</ul>

	<div class="cart-summary">
		<h3 class="cart-total__label"><?php echo get_string('cart_total', 'local_moodec');?></h3><h3 class="cart-total"><?php echo local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')) . local_moodec_cart_get_total();?></h3>
	</div>

	<div class="cart-actions">
		<form action="/local/moodec/pages/catalogue.php" method="GET">
			<input type="submit" value="<?php echo get_string('button_return_store_label', 'local_moodec');?>">
		</form>
		<form action="/local/moodec/pages/checkout.php" method="GET">
			<input type="submit" value="<?php echo get_string('button_checkout_label', 'local_moodec');?>">
		</form>
	</div>

	<?php } else {?>

	<p><?php echo get_string('cart_empty_message', 'local_moodec');?></p>

	<form action="/local/moodec/pages/catalogue.php" method="GET">
		<input type="submit" value="<?php echo get_string('button_return_store_label', 'local_moodec');?>">
	</form>

	<?php }?>
</div>


<?php
echo $OUTPUT->footer();