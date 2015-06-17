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

require_login();
// require_capability('local/moodec:checkout', $systemcontext);

$removedProducts = (array) json_decode(optional_param('enrolled', '', PARAM_TEXT));

// Get the cart in it's current state
$cart = local_moodec_get_cart();
$itemCount = 1;

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

$ipnData = sprintf('U:%d', $USER->id);

echo $OUTPUT->header();

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

	?>
<p><?php echo get_string('checkout_message', 'local_moodec');?></p>

<?php if (!!$removedProducts && is_array($removedProducts)) {
		printf("<p style='color: red;'>%s</p>", get_string('checkout_removed_courses_label', 'local_moodec'));
		echo "<ul>";
		foreach ($removedProducts as $product) {
			$thisCourse = get_course($product);
			printf('<li style="color: red;">%s</li>', $thisCourse->fullname);
		}
		echo "</ul>";
	}?>

<form class="cart-overview" action="https://www.paypal.com/cgi-bin/webscr" method="post">
	<input type="hidden" name="cmd" value="_cart">
	<input type="hidden" name="charset" value="utf-8" />
	<input type="hidden" name="upload" value="1">
	<input type="hidden" name="business" value="<?php echo get_config('local_moodec', 'paypalbusiness');?>">
	<input type="hidden" name="currency_code" value="<?php echo get_config('local_moodec', 'currency');?>">
	<input type="hidden" name="for_auction" value="false" />
	<input type="hidden" name="no_note" value="1" />
	<input type="hidden" name="no_shipping" value="1" />
	<input type="hidden" name="notify_url" value="<?php echo "$CFG->wwwroot/local/moodec/ipn.php"?>" />
	<input type="hidden" name="return" value="<?php echo "$CFG->wwwroot/local/moodec/pages/catalogue.php"?>" />
	<input type="hidden" name="cancel_return" value="<?php echo "$CFG->wwwroot/local/moodec/pages/cart.php"?>" />

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

			<input type="hidden" name="item_name_<?php echo $itemCount;?>" value="<?php
				if($variation === 0) { 
					echo $product->fullname;
				} else { 
					printf(
						'%s - %s',
					 	$product->fullname,
					 	$product->variations[$variation]->name
					);
				}?>">
			<input type="hidden" name="amount_<?php echo $itemCount;?>" value="<?php if($variation === 0) { echo $product->price; } else { echo $product->variations[$variation]->price;}?>">

		</li>

	<?php $ipnData .= sprintf('|C:%d,V:%d', $courseid, $variation);
		$itemCount++;}?>

	</ul>

	<div class="cart-summary">
		<h3 class="cart-total__label"><?php echo get_string('checkout_total', 'local_moodec');?></h3><h3 class="cart-total"><?php echo local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')) . local_moodec_cart_get_total();?></h3>
	</div>

	<div class="cart-actions">
		<input type="hidden" name="custom" value="<?php echo $ipnData;?>" />
		<input type="submit" name="submit"  value="<?php echo get_string('button_paypal_label', 'local_moodec');?>">
	</div>
</form>
<form action="/local/moodec/pages/catalogue.php" method="GET" class="back-to-shop">
	<input type="submit" value="<?php echo get_string('button_return_store_label', 'local_moodec');?>">
</form>

<?php }

echo $OUTPUT->footer();