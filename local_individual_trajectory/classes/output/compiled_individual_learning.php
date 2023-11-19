<?php
namespace local_individual_trajectory\output;

use context_course;
use DateInterval;
use DatePeriod;
use DateTime;
use renderable;
use templatable;
use renderer_base;
use stdClass;
use core_course\external\course_summary_exporter;
use core_competency\api as competency_api;
use core_competency\course_competency;
use core_competency\user_competency;
use core_competency\course_module_competency;
use core_competency\competency;


class compiled_individual_learning implements renderable, templatable {

    /** @var int $user_id */
    protected $user_id;
    protected $list_competency_id;

    /**
     * Construct this renderable.
     *
     * @param int $userid The user id
     * @param int $data_post The list competencies id
     */
    public function __construct($userid, $data_post) {
        $this->user_id = $userid;
        $this->list_competency_id = $data_post;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $PAGE;

        // Массив, в котором мы будем хранить курсы, которые надо изучить.
        $courses = [];

        // Массив, в котором мы будем хранить компетенции для освоения, курсы и соответствующие модули.
        $path_iet = [];

        // Массив для хранения id курсов, которые мы выводим.
        $list_id_courses = [];

        // Массив для хранения общей характеристики по ИОТ.
        $general_characteristics = array("labor_intensity" => 0, "duration" => 0, "kol_modules" => 0);

        // Получаем массив id компетенций, которые есть у пользователя.
        $usercompetency_complete = $DB->get_fieldset_select('competency_usercomp', 'competencyid', 'userid = :userid', ['userid' => $this->user_id]);

        foreach ($this->list_competency_id as $competency_id) {
            // Получаем список курсов и их данные.
            $list_courses = course_competency::list_courses($competency_id);

            // Для формирования массива пути
            $section_path = new stdClass();
            $section_path->competency_name = $DB->get_field('competency', 'shortname', ['id' => $competency_id]);
            $section_path->course_names = [];
            $section_path->count_course_names = 0;
            
            foreach ($list_courses as $course) {

                // Черновик фильтрации курсов.
                /*
                $fields = $DB->get_records_list('customfield_field', 'shortname', array('duration'));
                $temp = false;
                foreach ($fields as $field) {
                    $fieldvalue = $DB->get_record('customfield_data',array('fieldid' => $field->id, 'instanceid' => $course->id));
                    if ((int)$fieldvalue->value > 5)
                        $temp = true;
                }
                if ($temp === true)
                    continue;
                */
                
                $modinfo = get_fast_modinfo($course);

                if (!in_array($course->id, $list_id_courses)) {
                    // Создаем новый курс.
                    $new_course = new stdClass();

                    // Записываем основную информацию о курсе.
                    $new_course->id = $course->id;

                    // Компетенция для освоения.
                    $new_course->competency_name = $competency_name;
                    
                    $fullname = $course->fullname;
                    $split_full_name_course = explode(" ", $fullname);

                    // Получаем имя курса.
                    $name_course = "";
                    for($i = 2; $i <= count($split_full_name_course) - 4; $i++)
                        $name_course .= $split_full_name_course[$i] . " ";

                    $new_course->title = $name_course;
                    $new_course->title_short = mb_strimwidth($name_course, 0, 37, "...");
                    
                    $new_course->viewurl = "$CFG->wwwroot/course/view.php?id=$course->id";
                    $new_course->description = mb_strimwidth($course->summary, 0, 100, "...");
                    $new_course->institution = "ВолгГТУ";
                    
                    $competencies_course = competency_api::list_course_competencies($course->id);
                    $list_competency_name = "";
                    foreach ($competencies_course as $compet) {
                        $list_competency_name .= $compet['competency']->get('shortname') . " \n";
                    }
                    $new_course->competences = $list_competency_name;
                    $new_course->direction = $split_full_name_course[1];

                    $courseimage = course_summary_exporter::get_course_image($course);
                    if (!$courseimage) {
                        $output = $PAGE->get_renderer('local_individual_trajectory');
                        $courseimage = $output->get_generated_image_for_id($course->id);
                    }
                    $new_course->courseimage = $courseimage;

                    // Получаем дополнительную информацию по курсу.
                    // Сначала получаем дополнительные поля, которые хотим отобразить.
                    $fields = $DB->get_records_list('customfield_field', 'shortname', array('labor_intensity', 'duration'));
                    // Перебираем поля и сохраняем информацию о них.
                    $characteristics = [];
                    foreach ($fields as $field) {
                        $fieldvalue = $DB->get_record('customfield_data',array('fieldid' => $field->id, 'instanceid' => $course->id));
                        // Записываем в переменную всю необходимую информацию по курсу.
                        if ($fieldvalue->value) {
                            $characteristics[$field->shortname] = $fieldvalue->value;
                            $general_characteristics[$field->shortname] += (float)$fieldvalue->value;
                        } else {
                            $characteristics[$field->shortname] = "—";
                        }
                    }

                    // Заполняем характеристику "количество модулей" ИОТ.
                    $characteristics["kol_modules"] = count($modinfo->sections);
                    $general_characteristics["kol_modules"] += count($modinfo->sections);

                    // Для высчитывания процента выполненных компетенций.
                    $characteristics["performance_percentage"] = 0;

                    // Сохраняем характеристики.
                    $new_course->characteristics = $characteristics;

                    $new_course->course_modules = [];
                    $new_course->name_modules_str = "Модули: "; // Для вывода списка модулей.
                    $courses[] = $new_course; // Это работает как ссылка.

                    $list_id_courses[] = $course->id; // Сохраняем id курсов, которые отображаем.
                }
                else {
                    foreach($courses as $struct) {
                        if ($struct->id == $course->id) {
                            $new_course = $struct;
                            break;
                        }
                    }
                }

                // Для формирования массива пути по курсам
                $section_path_course = new stdClass();
                $section_path_course->course_name = $new_course->title;
                $section_path_course->course_modules = [];
                $section_path_course->count_course_modules = 0;
                
                $cmids = course_module_competency::list_course_modules($competency_id, $course->id);
                
                foreach ($cmids as $cmid) {
                    $mod = $modinfo->cms[$cmid];
                    $mod_std = new stdClass();
                    $mod_std->name = $mod->name;
                    $mod_std->url = $mod->url;
                    $new_course->course_modules[] = $mod_std;

                    // Для формирования массива пути по модулям курса.
                    $section_path_course->course_modules[] = $mod->name;
                    
                    if (strlen($new_course->name_modules_str) > 99)
                        $new_course->name_modules_str = mb_strimwidth($new_course->name_modules_str, 0, 100, "...");
                    else
                        $new_course->name_modules_str .= $mod_std->name . '; ';
                }

                $section_path_course->count_course_modules = count($section_path_course->course_modules);
                
                // Для rowspan в таблице.
                $section_path_course->first_module = array_shift($section_path_course->course_modules);

                $section_path->count_course_names += $section_path_course->count_course_modules;
                $section_path->course_names[] = $section_path_course;
            }

            // На случай, если наша компетенция не привязана ни к одному модулю.
            if (!$list_courses) {
                $section_path_course = new stdClass();
                $section_path_course->course_name = "-";
                $section_path_course->first_module = ["-"];
                $section_path_course->count_course_modules = 1;
                $section_path->course_names[] = $section_path_course;
                $section_path->count_course_names = 1;
            }

            // Для rowspan в таблице.
            $section_path->first_course = array_shift($section_path->course_names);
            $path_iet[] = $section_path;
        }


        // Графики для хранения списка компетенций по курсам ИОТ.
        $data->charts = [];

        $COLORS = ['#008000', '#FF0000', '#9ACD32', '#2E8B57', '#800000', '#00FF00', '#8B008B', '#C71585', '#4B0082', '#8A2BE2',
        '#FF4500', '#00FFFF', '#FF00FF', '#000080', '#FFFF00', '#20B2AA', '#A52A2A', '#0000FF'];
        $GRAY_COLOR = '#95a5a6';

        $all_chart_data = []; // Общая статистика по ИОТ.
        $all_colors = []; // Общая статистика по ИОТ.
        $all_labels = []; // Общая статистика по ИОТ.
        $temp_all_chart_data = 0;
        $sum_performance_percentage = 0; // Для подсчета общего процента выполнения ИОТ.

        /* Получаем данные о компетенциях для диаграмм */
        foreach($list_id_courses as $key => $course_id) {
            $usercompetencycourses = course_competency::list_competencies($course_id);

            $params = array('id' => $course_id);
            $course = $DB->get_record('course', $params);
            $modinfo = get_fast_modinfo($course, $this->user_id);

            $comp_statistic = [];
            $labels = [];

            foreach ($usercompetencycourses as $usercompetencycourse) { // Для всех компетенций.

                $total = 0;
                $success = 0;

                $competency_modulecomps = $DB->get_records('competency_modulecomp', array('competencyid' => $usercompetencycourse->get("id")));

                foreach ($competency_modulecomps as $competency_modulecomp) {
                    if(!array_key_exists($competency_modulecomp->cmid, $modinfo->cms)) continue;
                    $completion = $DB->get_record("course_modules_completion",
                                                array("coursemoduleid" => $competency_modulecomp->cmid, "userid" => $this->user_id));
                    $total += 1;
                    if (!empty($completion) && $completion->completionstate == "1") {
                        $success += 1;
                    } else {
                        $cm = $modinfo->get_cm($competency_modulecomp->cmid);
                        $grades = grade_get_grades($course_id, 'mod', $cm->modname, $cm->instance, $this->user_id);
                        $item_grades = $grades->items[0]->grades;
                        if (!empty($grades) && count($grades->items) > 0 && count($grades->items[0]->grades) > 0 && $item_grades[array_keys($item_grades)[0]]->grade != null) {
                            $success += 1;
                        }
        
                    }
                }

                $comp_statistic[] = array("total" => $total, "success" => $success, "name" => $usercompetencycourse->get("shortname"));
                $labels[] = $usercompetencycourse->get("shortname");
            }

            $all_labels = array_merge($all_labels, $labels); // Сохраняем в массиве для отображения общего графика по ИОТ.
            $labels[] = "Не выполнено";

            $success_all = 0; // Успешно закрытые или выполненные компетенции по каждому разделу.
            $total_all = 0; // Всего компетенций по всем разделам курса (каждый курс включает список компетенций).
            $chart_data = []; // Массив успешно закрытых компетенции по каждому разделу.
            foreach ($comp_statistic as $statistic) {
                $chart_data[] = $statistic["success"];
                $success_all += $statistic["success"];
                $total_all += $statistic["total"];
            }

            $colors = array_slice($COLORS, $colors ? count($colors) - 1 : 0, count($chart_data)); // Получает массив цветов столько же сколько есть пользовательских компетенций курса.
            $all_colors = array_merge($all_colors, $colors); // Сохраняем в массиве для отображения общего графика по ИОТ.
            $colors[] = $GRAY_COLOR; // Серый цвет для обозночения невыполненных заданий.

            $chart_data[] = $total_all - $success_all; // Количество невыполненных заданий.

            // Вычисляем процент выполненных компетенций курса.
            $courses[$key]->characteristics["performance_percentage"] = $success_all / $total_all * 100;
            $sum_performance_percentage += $success_all / $total_all * 100;

            $data_about_chart = new stdClass();
            $data_about_chart->id = $course_id;
            $data_about_chart->chart_data = json_encode($chart_data);
            $data_about_chart->colors = json_encode($colors);
            $data_about_chart->labels = json_encode($labels);

            // Сохраняем в массиве для отображения общего графика по ИОТ.
            $temp_all_chart_data += array_pop($chart_data);
            $all_chart_data = array_merge($all_chart_data, $chart_data);

            $data->charts[] = $data_about_chart;
        }

        // Последним графиком помещаем общий график по всей ИОТ.
        $data_about_chart = new stdClass();
        $data_about_chart->id = "all";
        $all_chart_data[] = $temp_all_chart_data;
        $all_colors[] = $GRAY_COLOR;
        $all_labels[] = "Не выполнено";
        $data_about_chart->chart_data = json_encode($all_chart_data);
        $data_about_chart->colors = json_encode($all_colors);
        $data_about_chart->labels = json_encode($all_labels);

        $data->charts[] = $data_about_chart;

        // Считаем общую характеристику процента выполнения ИОТ.
        $general_characteristics["performance_percentage"] = $sum_performance_percentage / (count($courses) * 100) * 100;

        // Сохраняем общую характеристику и список курсов.
        $data->general_characteristics = $general_characteristics;
        $data->courses = $courses;

        // Сохраняем путь ИОТ.
        $data->path_iet = $path_iet;

        return $data;
    }

}
