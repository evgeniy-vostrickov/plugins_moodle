<?php

use core_completion_external;
use core_course\external\course_summary_exporter;
use core_competency\api;
use core_competency\course_competency;
use core_competency\user_competency;
use core_competency\course_module_competency;
use core_competency\competency;

require_once('onlineedu_lib.php');
require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

if (!has_capability('moodle/site:config', context_system::instance())) {
    // Do not throw exception display an empty page with administration menu if visible for current user.
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->footer();
    exit;
}


$partner_id = "ffb79db3-f762-498c92b0-42fb7f4a8095"; //Идентификатор платформы, это пример.

$PAGE->requires->css(new moodle_url('/admin/tool/send_course_cards/src/style.css'));
$PAGE->set_title(get_string('scoss_send_course_cards', 'tool_scoss'));
$PAGE->set_heading(get_string('scoss_send_course_cards', 'tool_scoss'));
echo $OUTPUT->header();


// Проверяем нужно нам перестроить ИОТ или пользователь зашел первый раз.
if (isset($_POST) && $_POST['id_course']) {
    $id = $_POST['id_course'];

    // Получение данных о курсе для отправки на online.edu.
    // Инициализация начальными данными.
    $course = get_course($id);
    $context = context_course::instance($id);
    // $content_course = core_course_external::get_course_contents($course->id, array());
    // Массив для хранения данных о курсе.
    $mas_data_about_course = array();

    // // Парсим строку с полным названием курса ($course->fullname).
    // // Пример полного названия: "ФЭВТ 09.04.04 Разработка ABAP-приложений в среде SAP 1сем О_Н Кузнецова".
    // // В ней по шаблону находится: Факультет, Направление, Название, Очное/Вечернее направление, Фамилия преподавателя.
    $str = $course->fullname;
    $split_full_name_course = explode(" ", $str);

    // Получаем имя курса
    $name_course = "";
    for($i = 2; $i <= count($split_full_name_course) - 4; $i++)
        $name_course .= $split_full_name_course[$i] . " ";

    $mas_data_about_course['title'] = $name_course;
    // $mas_data_about_course['title'] = $course->fullname;
    $mas_data_about_course['external_url'] = "$CFG->wwwroot/course/view.php?id=$course->id";

    $logo_course = course_summary_exporter::get_course_image($course);
    if ($logo_course)
        $mas_data_about_course['image'] = $logo_course;
    else
        $mas_data_about_course['image'] = "нет логотипа";

    $mas_data_about_course['description'] = $course->summary;

    $mas_data_about_course['institution'] = "ВолгГТУ";

    $mas_data_about_course['teachers'] = array();


    // Получение списка учителей
    // Первый способ
    // Список пользователей, которые имеют права назначать роли (учителя).
    // $teachers = get_enrolled_users(context_course::instance($id), 'moodle/role:assign');
    // Второй способ
    $role_teachers = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $list_teachers = get_role_users($role_teachers->id, $context);

    foreach ($list_teachers as $teacher) {
        $data_teacher['display_name'] = $teacher->lastname . " " . $teacher->firstname . " " . $teacher->middlename;

        if ($teacher->department)
            $data_teacher['description'] = "Кафедра: " . $teacher->department;
        else
            $data_teacher['description'] = "нет описания";
        
        $json_data_teacher = json_encode($data_teacher);
        array_push($mas_data_about_course['teachers'], $json_data_teacher);
    }

    $content = "<ul>";
    $sections = $DB->get_records("course_sections", array('course' => $course->id), '', 'name');
    foreach($sections as $subject) {
        if (!$subject->name) {
            continue;
        }
        $content .= "<li>$subject->name</li>";
    }
    $mas_data_about_course['content'] = $content . "</ul>";

    $competencies = api::list_course_competencies($id);
    $list_competency_name = "";
    foreach ($competencies as $competency) {
        $list_competency_name .= $competency['competency']->get('shortname') . " \n";
    }
    $mas_data_about_course['competences'] = $list_competency_name;

    $mas_data_about_course['results'] = "нет";
    // Нужно как-то добавить название направления, например, "45.03.02 Лингвистика", "51.03.01 Культурология".
    $mas_data_about_course['direction'] = $split_full_name_course[1];
    $mas_data_about_course['requirements'] = array("Знания лексики на начальном уровне");
    $mas_data_about_course['credits'] = "нет";
    $mas_data_about_course['duration'] = "нет";
    $mas_data_about_course['started_at'] = date("d-m-Y", $course->startdate);
    $mas_data_about_course['finished_at'] = date("d-m-Y", $course->enddate);
    $mas_data_about_course['has_certificate'] = "Сертификат не предъявляется";

    $send_data_about_course = array('partner_id' => $partner_id);
    $items = array('items' => array(json_encode($mas_data_about_course)));
    $send_data_about_course['package'] = json_encode($items);

    print_r("<pre>");
    print_r($mas_data_about_course);
    print_r("</pre>");

    // Вызов функции выполнения CURL.
    // JSON_UNESCAPED_UNICODE - может быть не нужен.
    $curlresp = curlsend('/api/v2/registry/courses', json_encode($send_data_about_course), 'POST');
    // $curlresp = curlsend('/test_moodle', json_encode($send_data_about_course), 'POST');

}

echo $OUTPUT->footer();