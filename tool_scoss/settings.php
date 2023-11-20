<?php

/**
 * Puts the plugin actions into the admin settings tree.
 *
 * @package    tool
 * @subpackage tool_scoss
 * @copyright  2022
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create a MoodleNet category.
    $ADMIN->add('root', new admin_category('scoss', get_string('scoss', 'tool_scoss')));
    // Our settings page.
    $settings = new admin_externalpage('tool_scoss', get_string('scoss_requests', 'tool_scoss'), "$CFG->wwwroot/$CFG->admin/tool/scoss/index.php");
    $ADMIN->add('scoss', $settings);
}
