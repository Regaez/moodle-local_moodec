<?php
/**
 * Moodec Catalogue Page
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWords Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(__FILE__) . '/../../../config.php';
require_once $CFG->dirroot . '/local/moodec/lib.php';

$categoryID = optional_param('category', null, PARAM_INT);

$PAGE->set_url('/local/moodec/pages/catalogue.php');
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('catalogue_title', 'local_moodec'));

?>

<div class="filter-bar">
	<div class="filter__category">
		Category:
		<select name="category" id="category">
			<?php echo local_moodec_get_category_list($categoryID);?>
		</select>
	</div>
	<div class="filter__sort">
		Sort by:
		<select name="sort" id="sort">
			<option value="alpha">Title: A - Z</option>
			<option value="alpha2">Title: Z - A</option>
			<option value="price">Price: High to Low</option>
			<option value="price2">Price: Low to High</option>
			<option value="enrolment">Duration: High to Low</option>
			<option value="enrolment2">Duration: Low to High</option>
		</select>
	</div>
</div>

<div class="product-list">

<?php
$products = local_moodec_get_products($categoryID);

foreach ($products as $product) {

	$productURL = new moodle_url($CFG->wwwroot . '/local/moodec/pages/product.php', array('id' => $product->courseid));
	$imageURL = local_moodec_get_course_image_url($product->courseid);
	$category = $DB->get_record('course_categories', array('id' => $product->category));
	$categoryURL = new moodle_url($CFG->wwwroot . '/local/moodec/pages/catalogue.php', array('category' => $product->category));

	if (strlen($product->summary) < 100) {
		$summary = $product->summary;
	} else {
		$summary = substr($product->summary, 0, 100) . '...';
	}?>

	<div class="product-item">
		<div class="product-details">
			<?php if (!!$imageURL) {printf('<img src="%s" alt="image" class="product-image">', $imageURL);}?>
			<div class="product-details__wrapper">
				<h3 class="product-title"><a href="<?php echo $productURL;?>"><?php echo $product->fullname;?></a></h3>
				<p class="product-summary"><?php echo $summary;?></p>
				<p>Category: <a href="<?php echo $categoryURL;?>"><?php echo $category->name;?></a></p>
			</div>
		</div>
		<div class="product-actions">
			<h4 class="product-price"><?php echo '$' . $product->price;?></h4>
			<form action="/local/moodec/pages/cart.php" method="POST" class="product-form">
				<input type="hidden" name="id" value="<?php echo $product->courseid;?>">
				<input type="hidden" name="action" value="addToCart">
				<button class="product-form__add">Add to cart</button>
			</form>
		</div>
	</div>

<?php }?>

</div>

<?php echo $OUTPUT->footer();?>
