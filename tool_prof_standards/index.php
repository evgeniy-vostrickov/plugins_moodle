<?php
/**
 *
 * @package    tool
 * @subpackage tool_prof_standards
 */

use stdClass;
require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('tool_prof_standards');

$PAGE->requires->css(new moodle_url('./src/style.css'));
$PAGE->requires->css(new moodle_url('/local/individual_trajectory/src/style.css'));

$baseurl = new moodle_url('./index.php');

// По умолчанию пользователь не администратор.
$is_admin = false;

// Если не администратор, то показывать пустую страницу.
if (!has_capability('moodle/site:config', context_system::instance())) {
    // Do not throw exception display an empty page with administration menu if visible for current user.
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->footer();
    exit;
} else {
    // Для класса construction_prof_standards.
    $is_admin = true;
}

// Проверка поступивших данных из форм добавления/редактирования проф стандарта.
if (isset($_POST) && ($_POST["add_profstan"] || $_POST["edit_profstan"])) {
    $general_work_func = array();
    foreach (explode("\n", $_POST["general_work_func"]) as $work_func) {
        $general_work_func[] = rtrim($work_func);
    }

    $specific_work_func = array();
    foreach (explode("\n", $_POST["specific_work_func"]) as $work_func) {
        $specific_work_func[] = rtrim($work_func);
    }

    // Формирование проф стандарта, сохранение в базу данных и отображение списка проф стандартов.
    $record = (object) array(
        'num_standard' => $_POST["num_standard"],
        'name' => $_POST["name_standard"],
        'description' => $_POST["description"],
        'general_work_func' => implode(";", $general_work_func),
        'specific_work_func' => implode(";", $specific_work_func),
        'competence_list' => implode(";", $_POST["competencies"]),
    );

    if ($_POST["add_profstan"])
        $DB->insert_record('prof_standards', $record);
    elseif ($_POST["edit_profstan"] && $_POST["id_profstan"]) {
        $record->id = $_POST["id_profstan"];
        $DB->update_record('prof_standards', $record);
    }

    $PAGE->set_title(get_string('individuallearning', 'local_individual_trajectory'));
    $PAGE->set_heading(get_string('individuallearning', 'local_individual_trajectory'));
    echo $OUTPUT->header();

    // Отображаем профессиональные стандарты.
    $output = $PAGE->get_renderer('local_individual_trajectory');
    $page = new \local_individual_trajectory\output\construction_prof_standards($USER->id, $is_admin);
    echo $output->render($page);
} elseif (isset($_POST) && $_POST["delete_profstan"] && $_POST["id_profstan"]) {
    // Проверка на существовании проф стандарта и его удаление.
    if ($DB->record_exists_select('prof_standards', 'id = :id', array('id' => $_POST["id_profstan"]))) {
        $DB->delete_records('prof_standards', array('id' => $_POST["id_profstan"]));
    }

    $PAGE->set_title(get_string('individuallearning', 'local_individual_trajectory'));
    $PAGE->set_heading(get_string('individuallearning', 'local_individual_trajectory'));
    echo $OUTPUT->header();

    // Отображаем профессиональные стандарты.
    $output = $PAGE->get_renderer('local_individual_trajectory');
    $page = new \local_individual_trajectory\output\construction_prof_standards($USER->id, $is_admin);
    echo $output->render($page);
} elseif (isset($_POST) && $_POST["change_form"] && $_POST["prof_id"]) {
    // Отображение формы редактирования проф стандарта.
    $PAGE->set_title(get_string('edit_profstandards', 'tool_prof_standards'));
    $PAGE->set_heading(get_string('edit_profstandards', 'tool_prof_standards'));
    echo $OUTPUT->header();

    $output = $PAGE->get_renderer('tool_prof_standards');
    $page = new \tool_prof_standards\output\add_new_prof_standards($_POST["prof_id"]);
    echo $output->render($page);
} else {
    // Отображения формы добавления проф стандарта.
    $PAGE->set_title(get_string('add_profstandards', 'tool_prof_standards'));
    $PAGE->set_heading(get_string('add_profstandards', 'tool_prof_standards'));
    echo $OUTPUT->header();

    $output = $PAGE->get_renderer('tool_prof_standards');
    $page = new \tool_prof_standards\output\add_new_prof_standards(null);
    echo $output->render($page);
}

echo $OUTPUT->footer();