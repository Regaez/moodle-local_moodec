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

if (!($course = $DB->get_record('course', array('id' => $courseid)))) {
	print_error('invalidcourseid', 'error');
}

// $product = $DB->get_record('local_moodec_course', array('courseid' => $courseid));
$product = local_moodec_get_product($courseid);

if (!$product) {
	print_error('courseunavailable', 'error');
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('product_title', 'local_moodec', array('coursename' => $product->fullname)));

$imageURL = local_moodec_get_course_image_url($product->courseid);
?>

<div class="product-single">

	<?php if (!!$imageURL && !!get_config('local_moodec', 'page_product_show_image')) {?>
		<img src="<?php echo $imageURL;?>" alt="" class="product-image">
	<?php }?>

	<div class="product-details">

		<?php if (!!get_config('local_moodec', 'page_product_show_description')) {?>
		<div class="product-description">
			<?php echo local_moodec_format_course_summary($product->courseid);?>
		</div>
		<?php }?>

		<?php if (!!get_config('local_moodec', 'page_product_show_additional_description')) {?>
		<div class="additional-info">
			<?php echo $product->additional_info;?>
		</div>
		<?php }?>

		<?php if (!!get_config('local_moodec', 'page_catalogue_show_category')) {

		$category = $DB->get_record('course_categories', array('id' => $product->category));
		$categoryURL = new moodle_url($CFG->wwwroot . '/local/moodec/pages/catalogue.php', array('category' => $product->category));
		?>
		<p><?php echo get_string('course_list_category_label', 'local_moodec');?> <a href="<?php echo $categoryURL;?>"><?php echo $category->name;?></a></p>
		<?php }?>

		<h4><?php echo get_string('enrolment_duration_label', 'local_moodec');?></h4>
		<p><?php echo local_moodec_format_enrolment_duration($product->enrolment_duration);?></p>

		<h4><?php echo get_string('price_label', 'local_moodec');?> <span class="price"><?php echo local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')) . $product->price;?></span></h4>

		<?php
if (isloggedin() && is_enrolled(context_course::instance($product->courseid, MUST_EXIST))) {
	?>
			<div class="product-form">
				<button class="product-form__add" disabled="disabled"><?php echo get_string('button_enrolled_label', 'local_moodec');?></button>
			</div>

		<?php } else {?>

			<?php if($product->pricing_model === 'simple') { ?>

				<form action="/local/moodec/pages/cart.php" method="POST" class="product-single__form">
					<input type="hidden" name="action" value="addToCart">
					<input type="hidden" name="id" value="<?php echo $product->courseid;?>">
					<input type="submit" value="<?php echo get_string('button_add_label', 'local_moodec');?>">
				</form>

			<?php } else { ?>

				<form action="/local/moodec/pages/cart.php" method="POST" class="product-single__form">
					<input type="hidden" name="action" value="addVariationToCart">
					<select name="variation">
						
						<?php foreach($product->variations as $variation) {?>
						
							<option value="<?php echo $variation->variation_id; ?>">
								<?php echo $variation->name; ?>
							</option>
						
						<?php } ?>

					</select>
					<input type="hidden" name="id" value="<?php echo $product->courseid;?>">
					<input type="submit" value="<?php echo get_string('button_add_label', 'local_moodec');?>">
				</form>
			
			<?php } ?>

		<?php }?>
	</div>
</div>

<?php
if (!!get_config('local_moodec', 'page_product_show_related_products')) {

	$products = local_moodec_get_related_products($courseid, $product->category);
	$iterator = 0;
	if (is_array($products) && 0 < count($products)) {?>


<div class="related-products">

	<h4><?php echo get_string('product_related_label', 'local_moodec');?></h4>

	<ul class="grid-container">

		<?php foreach ($products as $product) {?>

		<li class="grid-item">
			<a href="<?php echo new moodle_url('/local/moodec/pages/product.php', array('id' => $product->courseid));?>">
				<img src="<?php echo local_moodec_get_course_image_url($product->courseid);?>" alt="" class="product-image">
				<h5><?php echo $product->fullname;?></h5>
			</a>
		</li>

		<?php $iterator++;if ($iterator > 2) {break;}}?>
	</ul>

</div>

<?php }}?>

<?php echo $OUTPUT->footer();
