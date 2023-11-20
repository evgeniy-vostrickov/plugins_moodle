<?php
namespace tool_scoss\output;

use context_course;
use renderable;
use templatable;
use renderer_base;
use stdClass;



class show_table_edu implements renderable, templatable {
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

        $list_records_DB = $DB->get_records('transfer_onlineedu', null, '', '*');

        $records = array();

        foreach ($list_records_DB as $record_DB) {
            $record = new stdClass();
            $record->id = $record_DB->id;
            $record->userid = $record_DB->userid;
            $record->user_scos = $record_DB->user_scos;
            $record->act = $record_DB->act;
            $record->date_transfer = $record_DB->date_transfer;
            $record->courseid = $record_DB->courseid;
            $record->course_scos_id = $record_DB->course_scos_id;
            $record->session_id = $record_DB->session_id;
            $record->progress = $record_DB->progress ?? "NULL";
            $record->rating = $record_DB->rating ?? "NULL";
            $record->rating_time = $record_DB->rating_time ?? "NULL";
            $record->rating_id_instance = $record_DB->rating_id_instance ?? "NULL";
            $record->checkpoint_name = $record_DB->checkpoint_name ?? "NULL";
            $record->description = $record_DB->description ?? "NULL";
            $records[] = $record;
        }

        $data->records = $records;

        return $data;
    }
}
