<?php

defined('MOODLE_INTERNAL') || die();

include_once($CFG->dirroot . "/course/renderer.php");
use core_course\external\course_summary_exporter;
use core_competency\api;

class theme_newmsmtheme_core_course_renderer extends core_course_renderer {
    /**
     * Renders course info box.
     *
     * @param stdClass $course
     * @return string
     */
    public function course_info_box(stdClass $course) {
        global $DB;

        $content = '';
        $content .= $this->output->box_start('generalbox info');
        $chelper = new coursecat_helper();
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED);
        $content .= $this->coursecat_coursebox($chelper, $course);
        

        // Отображение паспортов курса.
        $classes = trim('coursebox clearfix');
        // .coursebox
        $content .= html_writer::start_tag('div', array(
            'class' => $classes,
            'data-courseid' => $course->id,
            'data-type' => self::COURSECAT_TYPE_COURSE,
        ));

        $content .= html_writer::start_tag('div', array());
        $content .= "Ссылка для перехода к материалам курса: $CFG->wwwroot/course/view.php?id=$course->id";
        $content .= html_writer::end_tag('div');

        $logo_course = course_summary_exporter::get_course_image($course);
        if ($logo_course) {
            $content .= html_writer::start_tag('div', array());
            $content .= "Логотип онлайн-курса: $logo_course";
            $content .= html_writer::end_tag('div');
        }
        else {
            $content .= html_writer::start_tag('div', array());
            $content .= "Логотип онлайн-курса: нет логотипа";
            $content .= html_writer::end_tag('div');
        }

        $content .= html_writer::start_tag('div', array());
        $content .= "Правообладатель: ВолгГТУ";
        $content .= html_writer::end_tag('div');

        $content .= html_writer::start_tag('div', array());
        $content .= "Содержание курса:";
        $content .= html_writer::end_tag('div');

        $content .= html_writer::start_tag('ul', ['class' => 'teachers']);
        $sections = $DB->get_records("course_sections", array('course' => $course->id), '', 'name');
        foreach($sections as $subject) {
            if (!$subject->name) {
                continue;
            }
            $content .= html_writer::tag('li', $subject->name);
        }
        $content .= html_writer::end_tag('ul');

        $content .= html_writer::start_tag('div', array());
        $content .= "Формируемые компетенции:";
        $content .= html_writer::end_tag('div');
        
        $content .= html_writer::start_tag('ul', ['class' => 'teachers']);
        $competencies = api::list_course_competencies($course->id);
        foreach ($competencies as $competency) {
            $content .= html_writer::tag('li', $competency['competency']->get('shortname'));
        }
        $content .= html_writer::end_tag('ul');

        // $content .= html_writer::start_tag('div', array());
        // $content .= "Направление подготовки: ";
        // $content .= html_writer::end_tag('div');

        $field_entry_requirements = $DB->get_record('customfield_field', array('shortname' => 'entry_requirements'));
        $entry_requirements = $DB->get_record('customfield_data', array('fieldid' => $field_entry_requirements->id, 'instanceid' => $course->id));
        $entry_requirements = $entry_requirements ? $entry_requirements->value : "не задано";

        $content .= html_writer::start_tag('div', array());
        $content .= "Входные требования к обучающемуся: $entry_requirements";
        $content .= html_writer::end_tag('div');

        $field_learning_results = $DB->get_record('customfield_field', array('shortname' => 'learning_results'));
        $learning_results = $DB->get_record('customfield_data', array('fieldid' => $field_learning_results->id, 'instanceid' => $course->id));
        $learning_results = $learning_results ? $learning_results->value : "не задано";

        $content .= html_writer::start_tag('div', array());
        $content .= "Результаты обучения: $learning_results";
        $content .= html_writer::end_tag('div');

        $field_labor_intensity = $DB->get_record('customfield_field', array('shortname' => 'labor_intensity'));
        $labor_intensity = $DB->get_record('customfield_data', array('fieldid' => $field_labor_intensity->id, 'instanceid' => $course->id));
        $labor_intensity = $labor_intensity ? $labor_intensity->value : "не задано";
        
        $content .= html_writer::start_tag('div', array());
        $content .= "Трудоемкость онлайн-курса в з.е.: $labor_intensity";
        $content .= html_writer::end_tag('div');

        $field_duration = $DB->get_record('customfield_field', array('shortname' => 'duration'));
        $duration = $DB->get_record('customfield_data', array('fieldid' => $field_duration->id, 'instanceid' => $course->id));
        $duration = $duration ? $duration->value : "не задано";

        $content .= html_writer::start_tag('div', array());
        $content .= "Длительность онлайн-курса в неделях: $duration";
        $content .= html_writer::end_tag('div');

        $content .= html_writer::start_tag('div', array());
        $startdate = date("d-m-Y", $course->startdate);
        $content .= "Дата ближайшего запуска онлайн-курса: $startdate";
        $content .= html_writer::end_tag('div');

        $content .= html_writer::start_tag('div', array());
        $enddate = date("d-m-Y", $course->enddate);
        $content .= "Дата окончания курса: $enddate";
        $content .= html_writer::end_tag('div');

        $content .= html_writer::start_tag('div', array());
        $content .= "Информация о сертификате: сертификат не предъявляется";
        $content .= html_writer::end_tag('div');

        $content .= html_writer::end_tag('div'); // .coursebox

        
        $content .= $this->output->box_end();
        return $content;
    }
}