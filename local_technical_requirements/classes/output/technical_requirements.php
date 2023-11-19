<?php
namespace local_technical_requirements\output;

use context_course;
use renderable;
use templatable;
use renderer_base;
use stdClass;

class technical_requirements implements renderable, templatable {
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

        // $test = new stdClass();
        // $data->test = "Test";

        return $data;
    }

}
