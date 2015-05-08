<?php
/**
 * Moodec Capability definitions
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWords Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$capabilities = array(

	'local/moodec:manage' => array(

		'riskbitmask' => RISK_SPAM,

		'captype' => 'write',
		'contextlevel' => CONTEXT_COURSE,
		'archetypes' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW,
		),
	),

	// 'local/moodec:checkout' => array(

	// 	'riskbitmask' => RISK_SPAM,

	// 	'captype' => 'read',
	// 	'contextlevel' => CONTEXT_SYSTEM,
	// 	'archetypes' => array(
	// 		'guest' => CAP_PREVENT,
	// 		'user' => CAP_ALLOW,
	// 		'student' => CAP_ALLOW,
	// 		'teacher' => CAP_ALLOW,
	// 		'editingteacher' => CAP_ALLOW,
	// 		'manager' => CAP_ALLOW,
	// 	),
	// ),
);