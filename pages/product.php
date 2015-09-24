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

// Get the ID of the course to be displayed
$productid = required_param('id', PARAM_INT);

// Set PAGE variables
$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot . '/local/moodec/pages/product.php', array('id' => $productid));

// Check if the theme has a moodec pagelayout defined, otherwise use standard
if (array_key_exists('moodec_product', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec_product');
} else if(array_key_exists('moodec', $PAGE->theme->layouts)) {
	$PAGE->set_pagelayout('moodec');
} else {
	$PAGE->set_pagelayout('standard');
}

// Get the renderer for this page
$renderer = $PAGE->get_renderer('local_moodec');

// Include required javascript
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/local/moodec/js/product.js'));

// Get the product via the course id
$product = local_moodec_get_product($productid);

// Check if the product actually exists/is available
if (!$product) {
	print_error('courseunavailable', 'error');
}

// Course must exist for there to be a product shown
if (!($course = $DB->get_record('course', array('id' => $product->get_course_id())))) {
	print_error('invalidcourseid', 'error');
}

//needs to have the product verified before setting page heading & title
$PAGE->set_title(get_string('product_title', 'local_moodec', array('coursename' => $product->get_fullname() )));
$PAGE->set_heading(get_string('product_title', 'local_moodec', array('coursename' => $product->get_fullname() )));


echo $OUTPUT->header();

// Render the product page content
echo $renderer->single_product($product);

// Check if related products should be shown and output if so
if (!!get_config('local_moodec', 'page_product_show_related_products')) {
	echo $renderer->related_products($product);
}

echo $OUTPUT->footer();
