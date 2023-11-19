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

class report_for_user implements renderable, templatable {

    /** @var context $context */
    protected $context;
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
     * @param int $courseid The course id
     * @param int $userid The user id
     */
    public function __construct($courseid, $userid, $date_start, $date_end) {
        $this->course_id = $courseid;
        $this->user_id = $userid;
        $this->date_start = $date_start;
        $date_start_stmp = DateTime::createFromFormat('Y-m-d', $date_start);
        if ($date_start_stmp) $this->date_start_stmp = $date_start_stmp->getTimestamp();
        $date_end_stmp = DateTime::createFromFormat('Y-m-d', $date_end);
        if ($date_end_stmp) $this->date_end_stmp = $date_end_stmp->getTimestamp();
        $this->date_end = $date_end;
        $this->context = context_course::instance($courseid);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        $COLORS = ['#1abc9c', '#2ecc71', '#3498db', '#9b59b6', '#34495e', '#f1c40f', '#e67e22', '#e74c3c', '#2c3e50', '#8e44ad',
            '#f39c12', '#d35400', '#2980b9', '#16a085'];
        $GRAY_COLOR = '#95a5a6';

        $data = new stdClass();
        $data->courseid = $this->course_id;
        $data->currentuser = $this->user_id;
        $data->date_start = $this->date_start;
        $data->date_end = $this->date_end;
        $currentuser = $this->user_id;
        $course_id = $this->course_id;

        $params = array('id' => $course_id);
        $course = $DB->get_record('course', $params);

        $usercompetencycourses = api::list_user_competencies_in_course($course_id, $currentuser);


        $modinfo = get_fast_modinfo($course, $currentuser);// для отладки

        $comp_statistic = [];
        $labels = [];

        foreach ($usercompetencycourses as $usercompetencycourse) { // Для всех компетенций

            $total = 0;
            $success = 0;

            $competency = $usercompetencycourse->get_competency();
            if (!$this->is_in_period($competency)) continue;

            $competency_modulecomps = $DB->get_records('competency_modulecomp', array('competencyid' => $competency->get('id')));

            foreach ($competency_modulecomps as $competency_modulecomp) {
                if(!array_key_exists($competency_modulecomp->cmid, $modinfo->cms)) continue;
                $completion = $DB->get_record("course_modules_completion",
                                              array("coursemoduleid" => $competency_modulecomp->cmid, "userid" => $currentuser));
                $total += 1;
                if (!empty($completion) && $completion->completionstate == "1") {
                
                    $success += 1;
                } else {
                    $cm = $modinfo->get_cm($competency_modulecomp->cmid);
//                    $mod = $DB->get_record($cm->modname, array('id' => $cm->instance), '*', MUST_EXIST);
                    $grades = grade_get_grades($course_id, 'mod', $cm->modname, $cm->instance, $currentuser);
                    $item_grades = $grades->items[0]->grades;
                    if (!empty($grades) && count($grades->items) > 0 && count($grades->items[0]->grades) > 0 && $item_grades[array_keys($item_grades)[0]]->grade != null) {
                        $success += 1;
                    }
      
                }

            }

            $comp_statistic[] = array("total" => $total, "success" => $success, "name" => $competency->get("shortname"));
            $labels[] = $competency->get("shortname");

        }
        $labels[] = "Не выполнено";

        $success_all = 0; // Успешно закрытые или выполненные компетенции по каждому разделу.
        $total_all = 0; // Всего компетенций по всем разделам курса (каждый курс включает список компетенций).
        $chart_data = []; // Массив успешно закрытых компетенции по каждому разделу.
        foreach ($comp_statistic as $statistic) {
            $chart_data[] = $statistic["success"];
            $success_all += $statistic["success"];
            $total_all += $statistic["total"];
        }

        $colors = array_slice($COLORS, 0, count($chart_data)); // Получает массив цветов столько же сколько есть пользовательских компетенций курса.
        $colors[] = $GRAY_COLOR; // Серый цвет для обозночения невыполненных заданий.

        $chart_data[] = $total_all - $success_all; // Количество невыполненных заданий.

        $chart_data = json_encode($chart_data);
        $colors = json_encode($colors);
        $labels = json_encode($labels);

        $data->chart_data = $chart_data;
        $data->colors = $colors;
        $data->labels = $labels;


        $data->competencies_data = $this->get_completed_modules($usercompetencycourses, $currentuser, $modinfo, $DB);

        $data->competencies_data_unfinished = $this->get_uncompleted_modules($usercompetencycourses, $currentuser, $modinfo, $DB);

        $buf = $this->get_completed_modules_dates($usercompetencycourses, $currentuser, $modinfo, $course_id, $DB, $COLORS);
        $data->competencies_dates = $buf[0];
        $data->dates_labels = json_encode($buf[1]);

        return $data;
    }

    private function get_uncompleted_modules($usercompetencycourses, $currentuser, $modinfo, $DB) {
        $competencies_data_unfinished = [];

        foreach ($usercompetencycourses as $usercompetencycourse) { // Для всех компетенций.

            $competency = $usercompetencycourse->get_competency();

            if (!$this->is_in_period($competency)) continue;

            $competency_name = $competency->get("shortname");

            $competency_data = new stdClass();
            $competency_data->competency_name = $competency_name; // Название компетенции.
            $competency_data->modules = []; // Массив НЕ завершенных модулей.

            $competency_modulecomps = $DB->get_records('competency_modulecomp',
                                                       array('competencyid' => $competency->get('id')));

            foreach ($competency_modulecomps as $competency_modulecomp) {
                if(!array_key_exists($competency_modulecomp->cmid, $modinfo->cms)) continue;
                $mod = $modinfo->cms[$competency_modulecomp->cmid];
                $completion = $DB->get_record("course_modules_completion",
                                              array("coursemoduleid" => $competency_modulecomp->cmid, "userid" => $currentuser));
                $cm = $modinfo->get_cm($competency_modulecomp->cmid);
                $grades = grade_get_grades($this->course_id, 'mod', $cm->modname, $cm->instance, $currentuser);
            
                $item_grades = $grades->items[0]->grades;
                
                if ((empty($completion) || $completion->completionstate != "1") && (empty($grades) || count($grades->items) == 0 || $item_grades[array_keys($item_grades)[0]]->grade == null)) {
                    $mod_std = new stdClass();
                    $mod_std->name = $mod->name;
                    $mod_std->url = $mod->url;
                    $competency_data->modules[] = $mod_std;
                }
            }
            if (count($competency_data->modules) > 0) $competencies_data_unfinished[] = $competency_data;
        }
        return $competencies_data_unfinished;
    }

    private function get_completed_modules_dates($usercompetencycourses, $currentuser, $modinfo, $course_id, $DB, $COLORS) {
        $competencies_dates = [];

        foreach ($usercompetencycourses as $usercompetencycourse) { // Для всех компетенций.

            $competency = $usercompetencycourse->get_competency();

            if (!$this->is_in_period($competency)) continue;

            $competency_name = $competency->get("shortname");

            $competency_data = new stdClass(); // Дата закрытия компетенций.
            $competency_data->competency_name = $competency_name;
            $competency_data->dates_values = [];

            $competency_modulecomps = $DB->get_records('competency_modulecomp',
                                                       array('competencyid' => $competency->get('id'))); //TODO вынести отсюда.

            foreach ($competency_modulecomps as $competency_modulecomp) {
                if(!array_key_exists($competency_modulecomp->cmid, $modinfo->cms)) continue;
                $completion = $DB->get_record("course_modules_completion",
                                              array("coursemoduleid" => $competency_modulecomp->cmid, "userid" => $currentuser));//TODO вынести отсюда.

                if (!empty($completion) && $completion->completionstate == "1") {
                    $competency_data->dates_values[] = $completion->timemodified;
                } else {
                
                    $cm = $modinfo->get_cm($competency_modulecomp->cmid);
                    $grades = grade_get_grades($course_id, 'mod', $cm->modname, $cm->instance, $currentuser);
                    $item_grades = $grades->items[0]->grades;
                    if (!empty($grades) && count($grades->items) > 0 && count($grades->items[0]->grades) > 0 && $item_grades[array_keys($item_grades)[0]]->dategraded != null) {
                        $competency_data->dates_values[] = $item_grades[array_keys($item_grades)[0]]->dategraded;
                    }
                }
                
            }
            asort($competency_data->dates_values);

            $competencies_dates[] = $competency_data;

        }

        if (count($competencies_dates) == 0) return [[], []];

        //Находим начальную дату для графика.
        $min_date = PHP_INT_MAX;
        foreach ($competencies_dates as $competencies_date) {
            if (count($competencies_date->dates_values) > 0 && $competencies_date->dates_values[0] < $min_date) {
                $min_date = $competencies_date->dates_values[0];
            }
        }

        if ($min_date > time()) return [[], []];

        //С начальной даты, до текущей, для каждого дня.
        $begin = new DateTime();
        $begin->setTimestamp($min_date);
        $end = new DateTime();
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);

        $dates_labels = [];

        foreach ($period as $dt) {
            $dates_labels[] = $dt->format("Y-m-d");
        }

        $period = new DatePeriod($begin, $interval, $end);

        // Перебираем даты формирования подкомпетенций в модулях (на каждом цикле берется массив дат закрытия модулей).
        foreach ($competencies_dates as $i => $competencies_date) {
            $all_formatted_dates = [];

            // Перебор дат закрытия подкомпетенции модулей.
            foreach ($competencies_date->dates_values as $date) {
                $all_formatted_dates[] = date("Y-m-d", $date);
            }
            $completed_by_dates = array_count_values($all_formatted_dates); // Превращение в массив, где ключ - дата, значение - количество повторов.
            $current_completed = 0; // Число завершенных подкомпетенций модуля.
            $competencies_date->dates_values = []; // Обнуляем и будем хранить сколько подкомпетенций были сформированы за период.

            // Перебираем даты в периоде
            foreach ($period as $dt) {
                $date_formatted = $dt->format("Y-m-d"); // Берем дату
                if (array_key_exists($date_formatted, $completed_by_dates)) {
                    $current_completed += $completed_by_dates[$date_formatted]; // Если в эту дату было завершение, то увеличиваем счетчик.
                }
                $competencies_date->dates_values[] = $current_completed; // Сохраняем количество сформированных компетенций.
            }
            $competencies_date->color = $COLORS[$i]; // Берем цвет для покарски компетенции.
            $competencies_date->dates_values = json_encode($competencies_date->dates_values);
        }

        return [$competencies_dates, $dates_labels];

    }

    private function get_completed_modules($usercompetencycourses, $currentuser, $modinfo, $DB) {
        $competencies_data = [];
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

            $competency_modulecomps = $DB->get_records('competency_modulecomp',
                                                       array('competencyid' => $competency->get('id')));

            foreach ($competency_modulecomps as $competency_modulecomp) {
                if(!array_key_exists($competency_modulecomp->cmid, $modinfo->cms)) continue;
                $mod = $modinfo->cms[$competency_modulecomp->cmid];
                $completion = $DB->get_record("course_modules_completion",
                                              array("coursemoduleid" => $competency_modulecomp->cmid, "userid" => $currentuser));

                if (!empty($completion) && $completion->completionstate == "1") {
                    $mod_std = new stdClass();
                    $mod_std->name = $mod->name;
                    $mod_std->url = $mod->url;
                    $competency_data->modules[] = $mod_std;
                } else {
                    $cm = $modinfo->get_cm($competency_modulecomp->cmid);
                    $grades = grade_get_grades($this->course_id, 'mod', $cm->modname, $cm->instance, $currentuser);
                    $item_grades = $grades->items[0]->grades;
                    if (!empty($grades) && count($grades->items) > 0 && count($grades->items[0]->grades) > 0 && $item_grades[array_keys($item_grades)[0]]->grade != null) {
                        $mod_std = new stdClass();
                        $mod_std->name = $mod->name;
                        $mod_std->url = $mod->url;
                        $competency_data->modules[] = $mod_std;
                    }
                }
            }
            if (count($competency_data->modules) > 0) $competencies_data[] = $competency_data;
        }
        return $competencies_data;
    }

    private function is_in_period($object): bool
    {
        if ($this->date_end_stmp == null || $this->date_start_stmp == null) return true;
        return $object->get("timecreated") < $this->date_end_stmp && $object->get("timecreated") > $this->date_start_stmp;
    }

}
