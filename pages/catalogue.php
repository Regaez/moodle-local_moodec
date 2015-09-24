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
$PAGE->set_url($CFG->wwwroot . '/local/moodec/pages/catalogue.php');

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
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/local/moodec/js/catalogue.js'));

// Get the renderer for this page
$renderer = $PAGE->get_renderer('local_moodec');

list($sortfield, $sortorder) = local_moodec_extract_sort_vars($sort);

echo $OUTPUT->header();

?>

<h1 class="page__title"><?php echo get_string('catalogue_title', 'local_moodec'); ?></h1>

<?php 

// Render catalogue filter bar
echo $renderer->filter_bar($categoryID, $sort); 

// Get the products for this page
$products = local_moodec_get_products($page, $categoryID, $sortfield, $sortorder);

// Outputs this page of products
echo $renderer->catalogue($products);

// Get all products matching the filter parameters
$allProducts = local_moodec_get_products(-1, $categoryID, $sortfield, $sortorder);
// Pass them to the pagination function
echo $renderer->pagination($allProducts, $page, $categoryID, $sort);

echo $OUTPUT->footer();