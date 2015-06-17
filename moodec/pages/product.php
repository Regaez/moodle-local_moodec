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

// $product = $DB->get_record('local_moodec_course', array('courseid' => $courseid));
$product = local_moodec_get_product($courseid);

if (!$product) {
	print_error('courseunavailable', 'error');
}

//needs to have the product verified before setting page heading & title
$PAGE->set_title(get_string('product_title', 'local_moodec', array('coursename' => $product->fullname)));
$PAGE->set_heading(get_string('product_title', 'local_moodec', array('coursename' => $product->fullname)));

echo $OUTPUT->header();

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
		<?php 

			if( $product->pricing_model === 'simple') {
				printf('<p class="product-duration">%s</p>',local_moodec_format_enrolment_duration($product->enrolment_duration)
				);
			} else {
				$attr = '';

				foreach ($product->variations as $v) {
					$attr .= sprintf('data-tier-%d="%s" ', 
						$v->variation_id, 
						local_moodec_format_enrolment_duration($v->enrolment_duration)
					);	
				}

				$firstVariation = reset($product->variations);

				printf('<p class="product-duration" %s>%s</p>',
					$attr,
					local_moodec_format_enrolment_duration($firstVariation->enrolment_duration)
				);
			}
		?>

		<?php if($product->pricing_model === 'simple') { ?>
			<h4 class="product-price"><span class="label"><?php echo get_string('price_label', 'local_moodec');?></span> <?php echo local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')) . $product->price;?></h4>
		<?php } else { 
			$attr = '';

			foreach ($product->variations as $v) {
				$attr .= sprintf('data-tier-%d="%.2f" ', $v->variation_id, $v->price);	
			}

			$firstVariation = reset($product->variations);

			printf('<h4 class="product-price" %s>%s %s<span class="amount">%.2f</span></h4>',
				$attr,
				get_string('price_label', 'local_moodec'),
				local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')),
				$firstVariation->price
			);

		} ?>

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
					<select class="product-tier" name="variation">
						
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
