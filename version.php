<?php
/**
 * Moodec Version file
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->component = 'local_moodec';
$plugin->version = 2015100600;
$plugin->release = '2.8 (Build: 2015012900)';
$plugin->requires = 2014051200;
//$plugin->requires = 2014111000;
$plugin->maturity = MATURITY_BETA;
$plugin->dependencies = array(
	'enrol_moodec' => 2014111000,
);