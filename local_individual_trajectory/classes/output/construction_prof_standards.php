<?php
namespace local_individual_trajectory\output;

use context_course;
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

class construction_prof_standards implements renderable, templatable {

    /** @var int $user_id */
    protected $user_id;
    /** @var boolean $is_admin */
    protected $is_admin;

    /**
     * Construct this renderable.
     *
     * @param int $userid The user id
     */
    public function __construct($userid, $is_admin) {
        $this->user_id = $userid;
        $this->is_admin = $is_admin;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $PAGE;

        // Записываем кем является пользователь.
        $data->is_admin = $this->is_admin;

        // Проверка на наличи ИОТ у пользователя.
        if ($DB->record_exists_select('individual_trajectory', 'userid = :userid', array('userid' => $this->user_id))) {
            $data->is_individual_trajectory = true;
        } else {
            $data->is_individual_trajectory = false;
        }

        // Код по отображению проф стандартов.
        $get_prof_standards = $DB->get_records_select('prof_standards', '', array());
        $list_prof_standards = array();

        foreach ($get_prof_standards as $standard) {
            $prof_standard = new stdClass();
            $prof_standard->id = $standard->id;
            $prof_standard->num_standard = $standard->num_standard;
            $prof_standard->name = mb_strimwidth($standard->name, 0, 42, "...");
            $prof_standard->description = mb_strimwidth($standard->description, 0, 180, "...");
            $prof_standard->general_work_func = explode(";", $standard->general_work_func);
            $prof_standard->specific_work_func = explode(";", $standard->specific_work_func);
            $prof_standard->list_competencies = explode(";", $standard->competence_list);
            $prof_standard->count_competence = count(explode(";", $standard->competence_list));

            $list_id_course_profstandard = [];
            foreach ($prof_standard->list_competencies as $competency_id) {
                // Получаем список курсов и их данные.
                $list_courses = array_keys(course_competency::list_courses_min($competency_id));
                $list_id_course_profstandard = array_merge($list_id_course_profstandard, $list_courses);
            }

            // Убираем повторяющиеся id курсов.
            $list_id_course_profstandard = array_unique($list_id_course_profstandard);
            $prof_standard->count_course = count($list_id_course_profstandard);

            // Подсчет длительности освоения профстандарта.
            $duration = 0; // сумма длительности в неделях
            foreach ($list_id_course_profstandard as $course_id) {
                $fields = $DB->get_records_list('customfield_field', 'shortname', array('duration'));
                foreach ($fields as $field) {
                    $fieldvalue = $DB->get_record('customfield_data',array('fieldid' => $field->id, 'instanceid' => $course_id));
                    // Записываем в переменную всю необходимую информацию по курсу
                    if ($fieldvalue->value) {
                        $duration += (int)$fieldvalue->value;
                    }
                }
            }
            $prof_standard->count_duration = $duration;
            
            $list_prof_standards[] = $prof_standard;
        }

        $data->prof_standards = $list_prof_standards;


        return $data;
    }

}
