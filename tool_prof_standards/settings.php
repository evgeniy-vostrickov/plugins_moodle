<?php

/**
 * Puts the plugin actions into the admin settings tree.
 *
 * @package    tool
 * @subpackage tool_prof_standards
 * @copyright  2022
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create a MoodleNet category.
    $ADMIN->add('root', new admin_category('prof_standards', get_string('pluginname', 'tool_prof_standards')));
    // Our settings page.
    $settings = new admin_externalpage('tool_prof_standards', get_string('profstandards', 'tool_prof_standards'), "$CFG->wwwroot/$CFG->admin/tool/prof_standards/index.php");
    $ADMIN->add('prof_standards', $settings);
}
