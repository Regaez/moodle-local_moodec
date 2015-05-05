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

$PAGE->set_url('/local/moodec/pages/catalogue.php');
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('catalogue_title', 'local_moodec'));

?>

<div class="filter-bar">
	<div class="filter__category">
		Category:
		<select name="category" id="category">
			<option value="all">All</option>
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
$products = $DB->get_records('local_moodec_course', array('show_in_store' => 1));

foreach ($products as $product) {

	$thisCourse = get_course($product->courseid);

	$productURL = new moodle_url($CFG->wwwroot . '/local/moodec/pages/product.php', array('id' => $product->courseid));
	$imageURL = local_moodec_get_course_image_url($product->courseid);

	if (strlen($thisCourse->summary) < 100) {
		$summary = $thisCourse->summary;
	} else {
		$summary = substr($thisCourse->summary, 0, 100) . '...';
	}?>

	<div class="product-item">
		<div class="product-details">
			<?php if (!!$imageURL) {printf('<img src="%s" alt="image" class="product-image">', $imageURL);}?>
			<div class="product-details__wrapper">
				<h3 class="product-title"><a href="<?php echo $productURL;?>"><?php echo $thisCourse->fullname;?></a></h3>
				<p class="product-summary"><?php echo $summary;?></p>
				<p>Category: <a href="#">Miscellaneous</a></p>
			</div>
		</div>
		<div class="product-actions">
			<h4 class="product-price"><?php echo '$' . $product->price;?></h4>
			<form action="" class="product-form">
				<input type="hidden" name="id" value="<?php echo $product->courseid;?>">
				<button class="view">Read more</button>
				<button class="product-form__add">Add to cart</button>
			</form>
		</div>
	</div>

<?php }?>


<?php
// $products = $DB->get_records('local_moodec_course');

// foreach ($products as $product) {
// 	if (!!$product->show_in_store) {
// 		$thisCourse = get_course($product->courseid);
// 		// var_dump($thisCourse);
// 		echo "<div>";
// 		printf(
// 			"<a href='%s'>%s</a>",
// 			new moodle_url($CFG->wwwroot . '/local/moodec/pages/product.php', array('id' => $product->courseid)),
// 			$thisCourse->fullname
// 		);

// 		echo "</div>";
// 	}
// }
?>

</div>

<?php echo $OUTPUT->footer();?>
