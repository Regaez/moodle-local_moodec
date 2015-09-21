<?php
/**
 * Moodec Catalogue Page
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(__FILE__) . '/../../../config.php';
require_once $CFG->dirroot . '/local/moodec/lib.php';

$categoryID = optional_param('category', null, PARAM_INT);
$sort = optional_param('sort', null, PARAM_TEXT);
$page = optional_param('page', 1, PARAM_INT);

$systemcontext = context_system::instance();

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/moodec/pages/catalogue.php');

// Check if the theme has a moodec pagelayout defined, otherwise use standard
if (array_key_exists('moodec_catalogue', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec_catalogue');
} else if(array_key_exists('moodec', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec');
} else {
	$PAGE->set_pagelayout('standard');
}

$PAGE->set_title(get_string('catalogue_title', 'local_moodec'));
$PAGE->set_heading(get_string('catalogue_title', 'local_moodec'));
$PAGE->requires->jquery();
$PAGE->requires->js('/local/moodec/js/catalogue.js');

// Get the renderer for this page
$renderer = $PAGE->get_renderer('local_moodec');

$sortfield = 'sortorder';
$sortorder = 'ASC';

if ($sort !== null && 0 < strlen($sort) && strpos('-', $sort) !== -1) {
	$sortArray = explode('-', $sort);

	$sortfield = $sortArray[0];
	$sortorder = strtoupper($sortArray[1]);
}

// Get the cart in it's current state
$cart = new MoodecCart();

echo $OUTPUT->header();

?>

<h1 class="page__title"><?php echo get_string('catalogue_title', 'local_moodec'); ?></h1>

<?php 

// Render catalogue filter bar
echo $renderer->filter_bar($categoryID, $sort); 


$products = local_moodec_get_products($page, $categoryID, $sortfield, $sortorder);

if (is_array($products) && 0 < count($products)) {
	?>

<div class="product-list">

<?php
$iterator = 0;
	$itemLimit = (int) get_config('local_moodec', 'pagination');
	foreach ($products as $product) {

		$productURL = new moodle_url($CFG->wwwroot . '/local/moodec/pages/product.php', array('id' => $product->get_id()));
		$imageURL = $product->get_image_url();
		$category = $DB->get_record('course_categories', array('id' => $product->get_category_id()));
		$categoryURL = new moodle_url($CFG->wwwroot . '/local/moodec/pages/catalogue.php', array('category' => $product->get_category_id()));

		if (strlen($product->get_summary()) < 100) {
			$summary = $product->get_summary();
		} else {
			$summary = substr($product->get_summary(), 0, 100) . '...';
		}?>

	<div class="product-item">
		<div class="product-details">
			<?php if (!!$imageURL && !!get_config('local_moodec', 'page_catalogue_show_image')) {printf('<img src="%s" alt="image" class="product-image">', $imageURL);}?>
			<div class="product-details__wrapper">
				<h3 class="product-title">
					<?php if (!!get_config('local_moodec', 'page_product_enable')) {?>
					<a href="<?php echo $productURL;?>"><?php echo $product->get_fullname();?></a>
					<?php } else {
						echo $product->get_fullname();
					}?>
				</h3>

				<?php if (!!get_config('local_moodec', 'page_catalogue_show_description')) {?>
				<div class="product-summary"><?php echo $summary;?></div>
				<?php }?>

				<?php if (!!get_config('local_moodec', 'page_catalogue_show_additional_description')) {?>
				<div class="product-summary additional"><?php echo $product->get_description();?></div>
				<?php }?>

				<?php if(!!get_config('local_moodec', 'page_catalogue_show_duration')) { ?>
					<p><?php echo get_string('catalogue_enrolment_duration_label', 'local_moodec');?> <?php

						if( $product->get_type() === PRODUCT_TYPE_SIMPLE) {
							printf('<span class="product-duration">%s</span>', $product->get_duration());
						} else {
							$attr = '';

							foreach ($product->get_variations() as $v) {
								$attr .= sprintf('data-tier-%d="%s" ',
									$v->get_id(),
									$v->get_duration()
								);
							}

							list($firstVariation) = array_values($product->get_variations());

							printf('<span class="product-duration" %s>%s</span>',
								$attr,
								$firstVariation->get_duration()
							);
						}
					?></p>
				<?php } ?>

				<?php if (!!get_config('local_moodec', 'page_catalogue_show_category')) {?>
				<p><?php echo get_string('course_list_category_label', 'local_moodec');?> <a href="<?php echo $categoryURL;?>"><?php echo $category->name;?></a></p>
				<?php }?>
			</div>
		</div>
		<div class="product-actions">

			<?php if (!!get_config('local_moodec', 'page_catalogue_show_price')) {?>

				<?php if($product->get_type() === PRODUCT_TYPE_SIMPLE) { ?>
					<h4 class="product-price"><?php echo local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')) . $product->get_price();?></h4>
				<?php } else {
					$attr = '';

					foreach ($product->get_variations() as $v) {
						$attr .= sprintf('data-tier-%d="%.2f" ', $v->get_id(), $v->get_price());
					}

					list($firstVariation) = array_values($product->get_variations());

					printf('<h4 class="product-price" %s>%s<span class="amount">%.2f</span></h4>',
						$attr,
						local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')),
						$firstVariation->get_price()
					);

				} ?>
			<?php }?>

			<?php
			if (!!get_config('local_moodec', 'page_catalogue_show_button')) {
				if (isloggedin() && is_enrolled(context_course::instance($product->get_course_id(), MUST_EXIST))) {
				?>
				<div class="product-form">
					<button class="product-form__add button--enrolled" disabled="disabled"><?php echo get_string('button_enrolled_label', 'local_moodec');?></button>
				</div>

			<?php } else if( $cart->check($product->get_id()) ){?>

				<div class="product-single__form">
					<a href="<?php echo new moodle_url('/local/moodec/pages/cart.php'); ?>" class="product-form__add btn button--cart"><?php echo get_string('button_in_cart_label', 'local_moodec');?></a>
				</div>

			<?php } else {?>

				<?php if( $product->get_type() === PRODUCT_TYPE_SIMPLE) { ?>

				<form action="/local/moodec/pages/cart.php" method="POST" class="product-form">
					<input type="hidden" name="id" value="<?php echo $product->get_id();?>">
					<input type="hidden" name="action" value="addToCart">
					<button class="product-form__add"><?php echo get_string('button_add_label', 'local_moodec');?></button>
				</form>

				<?php } else { ?>

				<form action="/local/moodec/pages/cart.php" method="POST" class="product-form">
					<input type="hidden" name="id" value="<?php echo $product->get_id();?>">
					<input type="hidden" name="action" value="addVariationToCart">
					<select class="product-tier" name="variation">

						<?php foreach($product->get_variations() as $variation) {?>

							<option value="<?php echo $variation->get_id(); ?>">
								<?php echo $variation->get_name(); ?>
							</option>

						<?php } ?>

					</select>
					<button class="product-form__add"><?php echo get_string('button_add_label', 'local_moodec');?></button>
				</form>

				<?php } ?>
			<?php }
			}?>
		</div>
	</div>

<?php $iterator++;if ($iterator === $itemLimit) {break;}}?>

</div>

<?php local_moodec_output_pagination($products, $page, $categoryID, $sort);?>

<?php } else {
	printf(
		'<div class="catalogue-empty">%s</div>',
		get_string(
			'catalogue_empty',
			'local_moodec'
		)
	);
} 

echo $OUTPUT->footer();
