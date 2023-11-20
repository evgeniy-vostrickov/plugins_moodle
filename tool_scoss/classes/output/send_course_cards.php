<?php
namespace tool_scoss\output;

use context_course;
use renderable;
use templatable;
use renderer_base;
use stdClass;



class send_course_cards implements renderable, templatable {
    /**
     * Construct this renderable.
     *
     * @param int $userid The user id
     */
    public function __construct() {}

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $PAGE;

        $list_courses_DB = $DB->get_records('course', null, '', 'id, fullname');

        $courses = array();

        foreach ($list_courses_DB as $course) {
            $new_course = new stdClass();
            $new_course->id = $course->id;
            $new_course->fullname = $course->fullname;
            $courses[] = $new_course;
        }

        $data->name_page = "СЦОС";
        $data->courses = $courses;

        return $data;
    }

}
