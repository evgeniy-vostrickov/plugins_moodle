<?php
namespace tool_prof_standards\output;

use context_course;
use renderable;
use templatable;
use renderer_base;
use stdClass;



class add_new_prof_standards implements renderable, templatable {

    /** @var ProfStandart $prof_standard */
    protected $prof_id;

    /**
     * Construct this renderable.
     *
     * @param int $userid The user id
     */
    public function __construct($prof_id) {
        $this->prof_id = $prof_id;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $PAGE;

        $object_data_prof_standard = new stdClass();

        if ($this->prof_id) {
            // Получение стандарта для изменения.
            $prof_standard = $DB->get_record('prof_standards', array('id' => $this->prof_id));

            // Записывем id для обновления бд и редактирования проф стандарта.
            $data->id_prof_standard = $prof_standard->id;

            $object_data_prof_standard->name_standard = $prof_standard->name;
            $object_data_prof_standard->num_standard = $prof_standard->num_standard;
            $object_data_prof_standard->description = $prof_standard->description;
            $object_data_prof_standard->general_work_func = str_replace(";", "\n", $prof_standard->general_work_func);
            $object_data_prof_standard->specific_work_func = str_replace(";", "\n", $prof_standard->specific_work_func);
            $object_data_prof_standard->competence_list = explode(";", $prof_standard->competence_list);
        } else {
            $object_data_prof_standard->name_standard = '';
            $object_data_prof_standard->num_standard = '';
            $object_data_prof_standard->description = '';
            $object_data_prof_standard->general_work_func = '';
            $object_data_prof_standard->specific_work_func = '';
            $object_data_prof_standard->competence_list = array();
        }

        $data->prof_standard = $object_data_prof_standard;

        $list_competencies = $DB->get_records('competency', array(), '', 'id, shortname');

        $list_all_competencies = array();

        foreach ($list_competencies as $element_list) {
            $competency = new stdClass();
            $competency->id = $element_list->id;
            $competency->name = $element_list->shortname;
            $competency->temp = false;

            if (in_array($competency->id, $object_data_prof_standard->competence_list))
                $competency->checked = "checked";
            else
                $competency->checked = "";
            
            $list_all_competencies[] = $competency;
        }

        $data->list_all_competencies = $list_all_competencies;

        return $data;
    }

}
