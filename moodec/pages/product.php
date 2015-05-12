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

		<h4><?php echo get_string('enrolment_duration_label', 'local_moodec');?></h4>
		<p><?php echo local_moodec_format_enrolment_duration($moodecCourse->enrolment_duration);?></p>

		<h4><?php echo get_string('price_label', 'local_moodec');?> <span class="price"><?php echo local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')) . $moodecCourse->price;?></span></h4>

		<form action="/local/moodec/pages/cart.php" method="POST" class="product-single__form">
			<input type="hidden" name="action" value="addToCart">
			<input type="hidden" name="id" value="<?php echo $moodecCourse->courseid;?>">
			<input type="submit" value="<?php echo get_string('button_add_label', 'local_moodec');?>">
		</form>
	</div>
</div>

<?php echo $OUTPUT->footer();
