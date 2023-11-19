<?php
/**
 *
 * @package    local
 * @subpackage local_individual_trajectory
 */

use stdClass;
use core_completion_external;
use core_course\external\course_summary_exporter;
use core_competency\api as competency_api;
use core_competency\course_competency;
use core_competency\user_competency;
use core_competency\course_module_competency;
use core_competency\competency;

use context_course;
require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php'); // подключение файла с формами
require_once($CFG->dirroot .'/course/externallib.php'); //подключение файла с классом контента курса
require_once($CFG->dirroot .'/enrol/externallib.php'); //подключение файла с классом группы курса
require_once($CFG->libdir.'/gradelib.php'); // подключаем файл с классом оценок

// Подключение стилей и скриптов
$PAGE->requires->css(new moodle_url('/report/competency_statistic/src/cssprogress.min.css'));
$PAGE->requires->css(new moodle_url('/local/individual_trajectory/src/style.css'));
echo '<script src="'.new moodle_url('/report/competency_statistic/src/chart.min.js').'"></script>';

// Сохраняем id текущего пользователя.
$currentuser = $USER->id;
$baseurl = new moodle_url('/local/individual_trajectory/index.php');

$PAGE->set_title(get_string('individuallearning', 'local_individual_trajectory'));
$PAGE->set_heading(get_string('individuallearning', 'local_individual_trajectory'));
echo $OUTPUT->header();

// Проверяем нужно нам перестроить ИОТ или пользователь зашел первый раз.
if (isset($_POST) && $_POST['reset_individual_learning'] == "True") {
    // Проверка на существовании ИОТ.
    if ($DB->record_exists_select('individual_trajectory', 'userid = :userid', array('userid' => $currentuser))) {
        $DB->delete_records('individual_trajectory', array('userid' => $currentuser));
    }

    $output = $PAGE->get_renderer('local_individual_trajectory');
    $page = new \local_individual_trajectory\output\construction_learning_path($currentuser);
    echo $output->render($page);
} 
elseif ($DB->record_exists_select('individual_trajectory', 'userid = :userid', array('userid' => $currentuser))) {
    // Проверяем на наличие записи в базе данных.
    $trajectory_record = $DB->get_record_select('individual_trajectory', 'userid = :userid', array('userid' => $currentuser));

    $output = $PAGE->get_renderer('local_individual_trajectory');
    $page = new \local_individual_trajectory\output\compiled_individual_learning($currentuser, explode(";", $trajectory_record->competence_list));
    echo $output->render($page);
} else {
    $output = $PAGE->get_renderer('local_individual_trajectory');
    $page = new \local_individual_trajectory\output\construction_learning_path($currentuser);
    echo $output->render($page);
}

echo $OUTPUT->footer();



// Функция добавления нового раздела в profile/miscellaneous
// function local_individual_trajectory_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
//     $url = new moodle_url('/admin/tool/lp/plans.php', array('userid' => $user->id));
//     $node = new core_user\output\myprofile\node('miscellaneous', 'learningplans',
//                                                 get_string('learningplans', 'tool_lp'), null, $url);
//     $tree->add_node($node);
//     return true;
// }

// $test = user_competency::get_competency_by_usercompetencyid(1);





// !!!!!!!!! Дополнительные полезные функции !!!!!!!!!

// // Проверка доступа пользователя к курсу
// require_login();
// // Формирование страницы 
// $PAGE->set_title($course->shortname, 'local_individual_trajectory');
// $PAGE->set_heading($course->fullname, 'local_individual_trajectory');
// echo $OUTPUT->header();

// // Локальное название сайта
// // $name_web_moodle = 'localhost';


// Данные по компетенциям
// $params = array();
// $temp = $DB->get_record('competency', $params);
// print_r($temp);

// // Обращение к курсу через БД
// // $params = array('id' => $course->id);
// // $course = $DB->get_record('course', $params);
// // print_r($course);
// // echo '<p>';

// // Получение контекста курса
// // $context = context_course::instance($course->id);
// // print_r($context);
// // echo '<p>';

// // Вывод конкретного курса
// // $course = get_course(2);
// // print "<pre>";
// // print_r($course);
// // print "</pre>";

// // Парсим строку с полным именем курса
// // $str = "ФЭВТ 09.04.04 Разработка ABAP-приложений в среде SAP 1сем О_Н Кузнецова";
// // $split_str = explode(" ", $str);
// // print_r($split_str);

// // Получение списка всех курсов
// // $courses = get_courses();
// // print_r($courses);

// // Получение дополнительных полей курса
// // $fields = $DB->get_records_list('customfield_field', 'shortname', array('transfer_onlineedu', 'id_course_onlineedu', 'session_onlineedu'));
// // $fieldvalues = $DB->get_record('customfield_data',array('fieldid' => $fields[2]->id, 'instanceid' => $id));

// // print "<pre>";
// // print_r($fieldvalues);
// // print "</pre>";

// // Получение пользовательских компетенций
// // $usercompetencycourses = api::list_user_competencies_in_course($id, 6335);
// // foreach ($usercompetencycourses as $usercompetencycourse) {
// //     print "<pre>";
// //     print_r($usercompetencycourse);
// //     print "</pre>";
// // }

// // Получаем список всех курсов, на которые записан студент
// // $list_courses = enrol_get_users_courses($this->user_id, true);

// // $list_id_courses = array_keys($list_courses);
// // array_shift($list_id_courses);
// // print_r($list_id_courses);

// // Список доступных компетенций пользователя
// // $c = user_competency::get_multiple($this->user_id);