<?php
namespace report_competency_statistic\output;

use context_course;
use DateInterval;
use DatePeriod;
use DateTime;
use renderable;
use templatable;
use renderer_base;
use stdClass;
use core_competency\api;
use tool_lp\external\user_competency_summary_in_course_exporter;
use core_competency\user_competency;

class report_for_all_course implements renderable, templatable {

    /** @var int $course_id */
    protected $course_id;
    /** @var int $user_id */
    protected $user_id;
    /** @var string $date_start */
    protected $date_start;
    /** @var string $date_end */
    protected $date_end;
    /** @var string $date_start_stmp */
    protected $date_start_stmp;
    /** @var string $date_end_stmp */
    protected $date_end_stmp;
    /** @var array $competencies */
    protected $competencies;

    /**
     * Construct this renderable.
     *
     * @param int $userid The user id
     */
    public function __construct($userid, $date_start, $date_end) {
        $this->user_id = $userid;
        $this->date_start = $date_start;
        $date_start_stmp = DateTime::createFromFormat('Y-m-d', $date_start);
        if ($date_start_stmp) $this->date_start_stmp = $date_start_stmp->getTimestamp();
        $date_end_stmp = DateTime::createFromFormat('Y-m-d', $date_end);
        if ($date_end_stmp) $this->date_end_stmp = $date_end_stmp->getTimestamp();
        $this->date_end = $date_end;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        $COLORS = ['#008000', '#FF0000', '#9ACD32', '#2E8B57', '#800000', '#00FF00', '#8B008B', '#C71585', '#4B0082',
        '#8A2BE2', '#FF4500', '#00FFFF', '#FF00FF', '#000080', '#FFFF00', '#20B2AA', '#A52A2A', '#0000FF'];

        $data = new stdClass();

        // Получаем список всех курсов, на которые записан студент
        $list_courses = enrol_get_users_courses($this->user_id, true);
        $list_coursesid = array_keys($list_courses);
        array_shift($list_coursesid);
        
        $data->currentuser = $this->user_id;
        $data->date_start = $this->date_start;
        $data->date_end = $this->date_end;
        $currentuser = $this->user_id;

        $labels = []; // Названия сформированных компетенций.
        $chart_data = []; // Данные для формирования графика ([1, 1, ...]).
        $competencies_data = []; // Задания, которые формируют наши компетенции.

        // Перебираем все текущие курсы пользователя.
        foreach($list_coursesid as $course_id) {
            $this->course_id = $course_id;

            $params = array('id' => $course_id);
            $course = $DB->get_record('course', $params);

            $usercompetencycourses = api::list_user_competencies_in_course($course_id, $currentuser);

            $modinfo = get_fast_modinfo($course, $currentuser);

            foreach ($usercompetencycourses as $usercompetencycourse) { // Для всех компетенций в курсе.
                if ($usercompetencycourse->get('proficiency')) {
                    $competency = $usercompetencycourse->get_competency();
                    if (in_array($competency->get("shortname"), $labels)) {
                        $key = array_search($competency->get("shortname"), $labels);
                        $chart_data[$key] += 1;
                    }
                    else {
                        $labels[] = $competency->get("shortname");
                        $chart_data[] = 1;
                    }
                }
            }

            $this->get_completed_modules($usercompetencycourses, $currentuser, $modinfo, $competencies_data, $DB);
        }


        $colors = array_slice($COLORS, 0, count($labels)); // Получает массив цветов столько же сколько есть пользовательских компетенций курса.

        $colors = json_encode($colors);
        $labels = json_encode($labels);
        $chart_data = json_encode($chart_data);

        $data->colors = $colors;
        $data->labels = $labels;
        $data->chart_data = $chart_data;
        $data->competencies_data = $competencies_data;

        return $data;
    }


    private function get_completed_modules($usercompetencycourses, $currentuser, $modinfo, &$competencies_data, $DB) {
        global $PAGE;

        foreach ($usercompetencycourses as $usercompetencycourse) { // Для всех компетенций.

            $competency = $usercompetencycourse->get_competency();

            if (!$this->is_in_period($competency)) continue;

            $competency_name = $competency->get("shortname");

            $competency_data = new stdClass();
            $competency_data->competency_name = $competency_name;
            $competency_data->modules = []; // Массив завершенных модулей.

            $relatedcompetencies = api::list_related_competencies($competency->get('id')); // Список всех связанные компетенции.
            $user = $DB->get_record('user', array('id' => $currentuser));
            $evidence = api::list_evidence_in_course($currentuser, $this->course_id, $competency->get('id')); // Список всех доказательств компетентности пользователя в курсе.
            $course = $DB->get_record('course', array('id' => $this->course_id));

            $params = array(
                'competency' => $competency,
                'usercompetencycourse' => $usercompetencycourse,
                'evidence' => $evidence,
                'user' => $user,
                'course' => $course,
                'scale' => $competency->get_scale(),
                'relatedcompetencies' => $relatedcompetencies
            );
            
            // Класс для экспорта данных о компетенции пользователя с дополнительными связанными данными в плане.
            $exporter = new user_competency_summary_in_course_exporter(null, $params);
            $output = $PAGE->get_renderer('report_competency_statistic');
            $extra_data = $exporter->export($output);

            $competency_data->extra_data = $extra_data;

            $competency_modulecomps = $DB->get_records('competency_modulecomp', array('competencyid' => $competency->get('id')));

            foreach ($competency_modulecomps as $competency_modulecomp) {
                $mod = $modinfo->cms[$competency_modulecomp->cmid];

                if ($usercompetencycourse->get('proficiency')) {
                    $mod_std = new stdClass();
                    $mod_std->name = $mod->name;
                    $mod_std->url = $mod->url;
                    $competency_data->modules[] = $mod_std;
                }
            }
            
            if (count($competency_data->modules) > 0) $competencies_data[] = $competency_data;
        }
    }

    private function is_in_period($object): bool
    {
        if ($this->date_end_stmp == null || $this->date_start_stmp == null) return true;
        return $object->get("timecreated") < $this->date_end_stmp && $object->get("timecreated") > $this->date_start_stmp;
    }

}
