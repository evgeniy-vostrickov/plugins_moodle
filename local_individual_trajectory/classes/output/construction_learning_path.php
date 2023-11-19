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



class construction_learning_path implements renderable, templatable {

    /** @var int $user_id */
    protected $user_id;

    /**
     * Construct this renderable.
     *
     * @param int $userid The user id
     */
    public function __construct($userid) {
        $this->user_id = $userid;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $PAGE;

        /* Код по отображению неосвоенных компетенций пользователя. */
        // Массив, в котором мы будем хранить id компетенций.
        $new_usercompetencies = [];

        // Список всех компетенций.
        $competencies = competency_api::list_competencies([]);

        // Получаем массив id компетенций, которые есть у пользователя
        $usercompetency_complete = $DB->get_fieldset_select('competency_usercomp', 'competencyid', 'userid = :userid', ['userid' => $this->user_id]);

        foreach ($competencies as $competency) {
            // Просматриваем только те компетенции, которые мы не освоили.
            if (!in_array($competency->get("id"), $usercompetency_complete)) {
                $new_competency = new stdClass();
                $new_competency->id = $competency->get("id");
                $new_competency->title = $competency->get("shortname");
                $new_usercompetencies[] = $new_competency;
            }
        }

        $data->new_usercompetencies = $new_usercompetencies;
        $data->currentuser = $this->user_id;
        

        // Выводим диаграмму компетенций.
        $COLORS = ['#008000', '#FF0000', '#9ACD32', '#2E8B57', '#800000', '#00FF00', '#8B008B', '#C71585', '#4B0082',
        '#8A2BE2', '#FF4500', '#00FFFF', '#FF00FF', '#000080', '#FFFF00', '#20B2AA', '#A52A2A', '#0000FF'];

        $labels = [];
        $chart_data = [];

        // Получаем массив компетенции, которые есть у пользователя.
        // $mas_id_competency_usercomp = $DB->get_fieldset_select('competency_usercomp', 'competencyid', 'userid = :userid AND proficiency = 1', ['userid' => $this->user_id]);
        $mas_id_competency_usercomp = $DB->get_fieldset_select('competency_usercomp', 'competencyid', 'userid = :userid', ['userid' => $this->user_id]);
        if (!empty($mas_id_competency_usercomp)) {
            $mas_competency_usercomp = $DB->get_fieldset_select('competency', 'shortname', 'id IN (' . join(', ', $mas_id_competency_usercomp) . ')', []);
            
            // Перебираем компетенции и заполняем массив labels и chart_data.
            foreach ($mas_competency_usercomp as $competency) {
                $labels[] = $competency;
                $chart_data[] = 1;
            }

            $colors = array_slice($COLORS, 0, count($labels));
        }
        else {
            $mas_competency_usercomp = [];
            $labels[] = "Нет освоенных компетенций";
            $chart_data[] = 1;
            $colors = ["#95a5a6"];
        }

        $data->my_list_competency = $labels;
        
        $colors = json_encode($colors);
        $labels = json_encode($labels);
        $chart_data = json_encode($chart_data);

        $data->colors = $colors;
        $data->labels = $labels;
        $data->chart_data = $chart_data;

        return $data;
    }

}
