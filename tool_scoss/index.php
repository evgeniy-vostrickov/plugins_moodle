<?php
/**
 *
 * @package    tool
 * @subpackage tool_scoss
 */

use stdClass;
require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// echo "Help";
admin_externalpage_setup('tool_scoss');

$PAGE->requires->css(new moodle_url('./src/style.css'));

$baseurl = new moodle_url('./index.php');

// По умолчанию пользователь не администратор.
$is_admin = false;

// Если не администратор, то показывать пустую страницу.
if (!has_capability('moodle/site:config', context_system::instance())) {
    // Не выдавать исключение: отображать пустую страницу с меню администрирования.
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->footer();
    exit;
} else {
    $is_admin = true;
}

$PAGE->set_title(get_string('scoss_send_course_cards', 'tool_scoss'));
$PAGE->set_heading(get_string('scoss_send_course_cards', 'tool_scoss'));
echo $OUTPUT->header();

$output = $PAGE->get_renderer('tool_scoss');
$page = new \tool_scoss\output\send_course_cards();
echo $output->render($page);

echo $OUTPUT->footer();