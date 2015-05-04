<?php
/**
 * Moodec Library file
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWords Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Display the LTI settings in the course settings block
 * For 2.3 and onwards
 *
 * @param  settings_navigation $nav     The settings navigatin object
 * @param  stdclass            $context Course context
 */
function local_moodec_extends_settings_navigation(settings_navigation $nav, $context) {
	if ($context->contextlevel >= CONTEXT_COURSE and ($branch = $nav->get('courseadmin'))
		and has_capability('moodle/course:update', $context)) {
		$ltiurl = new moodle_url('/local/moodec/settings/course.php', array('courseid' => $context->instanceid));
		$branch->add(get_string('pluginname', 'local_moodec'), $ltiurl, $nav::TYPE_CONTAINER, null, 'moodec' . $context->instanceid);
	}
}