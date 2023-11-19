<?php
/**
 *
 * @package    local
 * @subpackage local_individual_trajectory
 */

require_once('../../config.php');

$PAGE->requires->css(new moodle_url('/local/individual_trajectory/src/cssprogress.min.css'));
$PAGE->requires->css(new moodle_url('/local/individual_trajectory/src/style.css'));
echo '<script src="'.new moodle_url('/local/individual_trajectory/src/chart.min.js').'"></script>';

$currentuser = $USER->id;
$baseurl = new moodle_url('/local/individual_trajectory/index.php');

$PAGE->set_title(get_string('individuallearning', 'local_individual_trajectory'));
$PAGE->set_heading(get_string('individuallearning', 'local_individual_trajectory'));
echo $OUTPUT->header();

// Отображаем профессиональные стандарты
$output = $PAGE->get_renderer('local_individual_trajectory'); // получение объекта рендера (файл local/individual_trajectory/classes/output/renderer.php)

// Проверка на пользователя администратора
$is_admin = false;
if(has_capability('moodle/site:config', context_system::instance()))
    $is_admin = true;

$page = new \local_individual_trajectory\output\construction_prof_standards($currentuser, $is_admin);
echo $output->render($page);
echo $OUTPUT->footer();