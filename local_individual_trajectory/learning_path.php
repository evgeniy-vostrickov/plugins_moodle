<?php
/**
 *
 * @package    local
 * @subpackage local_individual_trajectory
 */

require_once('../../config.php');

$PAGE->requires->css(new moodle_url('/report/competency_statistic/src/cssprogress.min.css'));
$PAGE->requires->css(new moodle_url('/local/individual_trajectory/src/style.css'));
echo '<script src="'.new moodle_url('/report/competency_statistic/src/chart.min.js').'"></script>';

$currentuser = $USER->id;
$baseurl = new moodle_url('/local/individual_trajectory/index.php');

$PAGE->set_title(get_string('individuallearning', 'local_individual_trajectory'));
$PAGE->set_heading(get_string('individuallearning', 'local_individual_trajectory'));
echo $OUTPUT->header();

// Если кнопка была нажата, то выполняем следующие действия.
if (isset($_POST) && count($_POST)) {
    // Проверяем откуда пришел запрос: со сраницы выбора компетенций или со страницы профстандартов.
    if ($_POST['prof_id']) {
        $obj_competencies = $DB->get_record('prof_standards', array('id' => $_POST['prof_id']), 'competence_list');
        $mas_competencies = explode(";", $obj_competencies->competence_list);
    }
    else {
        // $laboriousness = $_POST['laboriousness'];
        // $duration = $_POST['duration'];
        $mas_competencies = $_POST['list_competencies_select'];
    }
    
    // Проверка на существовании ИОТ.
    if (!$DB->record_exists_select('individual_trajectory', 'userid = :userid', array('userid' => $currentuser))) {
        // Формирование ИОТ, сохранение в базу данных и отображение.
        $record = (object) array(
            'userid' => $currentuser,
            'competence_list' => implode(";", $mas_competencies),
            'timecreated' => time(),
        );

        $DB->insert_record('individual_trajectory', $record);
    }

    $output = $PAGE->get_renderer('local_individual_trajectory');
    $page = new \local_individual_trajectory\output\compiled_individual_learning($currentuser, $mas_competencies);
    echo $output->render($page);
} else {
    $output = $PAGE->get_renderer('local_individual_trajectory');
    $page = new \local_individual_trajectory\output\construction_learning_path($currentuser);
    echo $output->render($page);
}

echo $OUTPUT->footer();