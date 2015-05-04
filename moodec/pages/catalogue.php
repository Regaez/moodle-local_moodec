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

echo $OUTPUT->heading(get_string('pluginname', 'local_moodec'));

$products = $DB->get_records('local_moodec_course');

foreach ($products as $product) {
	if (!!$product->show_in_store) {
		$thisCourse = get_course($product->courseid);
		echo "<div>";
		printf(
			"<a href='%s'>%s</a>",
			new moodle_url($CFG->wwwroot . '/local/moodec/pages/product.php', array('id' => $product->courseid)),
			$thisCourse->fullname
		);

		echo "</div>";
	}
}

echo $OUTPUT->footer();
