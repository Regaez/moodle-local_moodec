<?php
/**
 * Moodec Product Page
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(__FILE__) . '/../../../config.php';
require_once $CFG->dirroot . '/local/moodec/lib.php';

$courseid = required_param('id', PARAM_INT);

$systemcontext = context_system::instance();

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/moodec/pages/product.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->requires->jquery();

if (!($course = $DB->get_record('course', array('id' => $courseid)))) {
	print_error('invalidcourseid', 'error');
}

$product = local_moodec_get_product($courseid);

if (!$product) {
	print_error('courseunavailable', 'error');
}

//needs to have the product verified before setting page heading & title
$PAGE->set_title(get_string('product_title', 'local_moodec', array('coursename' => $product->fullname)));
$PAGE->set_heading(get_string('product_title', 'local_moodec', array('coursename' => $product->fullname)));

$renderer = $PAGE->get_renderer('local_moodec');

// Get the cart in it's current state
$cart = local_moodec_get_cart();

echo $OUTPUT->header();

echo $renderer->single_product($product);

if (!!get_config('local_moodec', 'page_product_show_related_products')) {

	$products = local_moodec_get_related_products($courseid, $product->category);
	$iterator = 0;
	if (is_array($products) && 0 < count($products)) {?>


<div class="related-products">

	<h2 class="related-products__title"><?php echo get_string('product_related_label', 'local_moodec');?></h2>

	<ul class="grid-container">

		<?php foreach ($products as $product) {?>

		<li class="grid-item">
			<img src="<?php echo local_moodec_get_course_image_url($product->courseid);?>" alt="" class="product-image">
			<h5><?php echo $product->fullname;?></h5>
			<a href="<?php echo new moodle_url('/local/moodec/pages/product.php', array('id' => $product->courseid));?>" class="product-view btn">
				<?php echo get_string('product_related_button_label', 'local_moodec'); ?>
			</a>
		</li>

		<?php $iterator++;if ($iterator > 2) {break;}}?>
	</ul>

</div>

<?php }}?>

<script>
	$('.product-tier').on('change', function(){
		var id = $(this).val();

		// Update price
		var newPrice = $('.product-price').attr('data-tier-' + id);
		$('.product-price .amount').text(newPrice);

		// Update course duration
		var newDuration = $('.product-duration').attr('data-tier-' + id);
		$('.product-duration').text(newDuration);
	});
</script>

<?php echo $OUTPUT->footer();
