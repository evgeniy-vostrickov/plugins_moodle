<?php

use core_completion_external;
use core_course\external\course_summary_exporter;

// В этой библиотеке функция выполнения CURL
require_once('onlineedu_lib.php');
require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Если не администратор, то показывать пустую страницу.
if (!has_capability('moodle/site:config', context_system::instance())) {
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->footer();
    exit;
}


$PAGE->requires->css(new moodle_url('/admin/tool/scoss/src/style.css'));
$PAGE->set_title(get_string('scoss_show_table_edu', 'tool_scoss'));
$PAGE->set_heading(get_string('scoss_show_table_edu', 'tool_scoss'));
echo $OUTPUT->header();

$output = $PAGE->get_renderer('tool_scoss');
$page = new \tool_scoss\output\show_table_edu();
echo $output->render($page);

echo $OUTPUT->footer();