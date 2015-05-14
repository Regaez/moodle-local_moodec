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

$moodecCourse = $DB->get_record('local_moodec_course', array('courseid' => $courseid));
$course = get_course($courseid);

if (!$moodecCourse->show_in_store) {
	print_error('courseunavailable', 'error');
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('product_title', 'local_moodec', array('coursename' => $course->fullname)));

?>

<div class="product-single">
	<img src="<?php echo local_moodec_get_course_image_url($moodecCourse->courseid);?>" alt="" class="product-image">
	<div class="product-details">
		<div class="product-description">
			<?php echo local_moodec_format_course_summary($moodecCourse->courseid);?>
		</div>

		<div class="additional-info">
			<?php echo $moodecCourse->additional_info;?>
		</div>

		<h4><?php echo get_string('enrolment_duration_label', 'local_moodec');?></h4>
		<p><?php echo local_moodec_format_enrolment_duration($moodecCourse->enrolment_duration);?></p>

		<h4><?php echo get_string('price_label', 'local_moodec');?> <span class="price"><?php echo local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')) . $moodecCourse->price;?></span></h4>

		<?php
if (isloggedin() && is_enrolled(context_course::instance($moodecCourse->courseid, MUST_EXIST))) {
	?>
			<div class="product-form">
				<button class="product-form__add" disabled="disabled"><?php echo get_string('button_enrolled_label', 'local_moodec');?></button>
			</div>

		<?php } else {?>

		<form action="/local/moodec/pages/cart.php" method="POST" class="product-single__form">
			<input type="hidden" name="action" value="addToCart">
			<input type="hidden" name="id" value="<?php echo $moodecCourse->courseid;?>">
			<input type="submit" value="<?php echo get_string('button_add_label', 'local_moodec');?>">
		</form>

		<?php }?>
	</div>
</div>

<?php $products = local_moodec_get_related_products($courseid, $course->category);
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

<?php }?>

<?php echo $OUTPUT->footer();
