<?php
/**
 *Moodec Cart Page
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWords Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(__FILE__) . '/../../../config.php';
require_once $CFG->dirroot . '/local/moodec/lib.php';

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

if (isset($_POST['action']) && $_POST['action'] === 'removeFromCart') {
	// Updates the cart var with the new addition
	$cart = local_moodec_cart_remove($_POST['id']);
	// redirect back to the course page
	redirect(new moodle_url('/local/moodec/pages/cart.php'));
}

//
// TODO: Render cart page
//
echo $OUTPUT->header();

echo $OUTPUT->heading('Cart');

?>


<div class="cart-overview">

	<ul class="products">

	<?php foreach ($cart['courses'] as $product => $value) {

	$moodecCourse = $DB->get_record('local_moodec_course', array('courseid' => $product));
	$thisCourse = get_course($product);?>

		<li class="product-item">
			<h4 class="product-title"><?php echo $thisCourse->fullname;?></h4>
			<div class="product-price">$<?php echo $moodecCourse->price;?></div>
			<form class="product__form" action="" method="POST">
				<input type="hidden" name="id" value="<?php echo $product;?>">
				<input type="hidden" name="action" value="removeFromCart">
				<input class="form__submit" type="submit" value="Remove">
			</form>
		</li>

	<?php }?>

	</ul>

	<div class="cart-summary">
		<h3 class="cart-total__label">Total:</h3><h3 class="cart-total">$<?php echo local_moodec_cart_get_total();?></h3>
	</div>

	<div class="cart-actions">
		<button>Return to store</button>
		<button>Proceed to checkout</button>
	</div>
</div>


<?php
echo $OUTPUT->footer();