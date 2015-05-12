<?php
/**
 * Moodec Settings file
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWords Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot . '/local/moodec/lib.php';

if ($hassiteconfig) {
	// needs this condition or there is error on login page

	$ADMIN->add('root', new admin_category('moodec', get_string('pluginname', 'local_moodec')));

	$ADMIN->add('moodec', new admin_externalpage('moodecsettings', get_string('moodec_settings', 'local_moodec'),
		$CFG->wwwroot . '/admin/settings.php?section=local_moodec', 'moodle/course:update'));

	$settings = new admin_settingpage('local_moodec', get_string('pluginname', 'local_moodec'));
	$ADMIN->add('localplugins', $settings);

	$settings->add(new admin_setting_configtext('local_moodec/paypalbusiness', get_string('businessemail', 'local_moodec'), get_string('businessemail_desc', 'local_moodec'), '', PARAM_EMAIL));

	$paypalcurrencies = local_moodec_get_currencies();
	$settings->add(new admin_setting_configselect('local_moodec/currency', get_string('currency', 'local_moodec'), '', 'USD', $paypalcurrencies));

}