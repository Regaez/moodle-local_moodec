<?php
/**
 * Moodec Product Page
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWords Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(__FILE__) . '/../../../config.php';
require_once $CFG->dirroot . '/local/moodec/lib.php';

$courseid = required_param('id', PARAM_INT);

if (!($course = $DB->get_record('course', array('id' => $courseid)))) {
	print_error('invalidcourseid', 'error');
}

$PAGE->set_url('/local/moodec/pages/product.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

echo $OUTPUT->heading($course->fullname);

echo "<p>Product details:</p>";
echo "<pre>";
var_dump($course);
echo "</pre>";

echo $OUTPUT->footer();
