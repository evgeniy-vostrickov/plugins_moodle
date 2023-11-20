<?php

/**
 * Renderer class for tool_scoss
 *
 * @package    tool_scoss
 */

namespace tool_scoss\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use stdClass;

/**
 * Renderer class for competency statistic report
 *
 * @package    tool_scoss
 */
class renderer extends plugin_renderer_base {

    /**
     * @param send_course_cards $page
     * @return string html for the page
     */
    public function render_send_course_cards(send_course_cards $page): string
    {
        $data = $page->export_for_template($this);
        return parent::render_from_template('tool_scoss/send_course_cards', $data);
    }
    
    /**
     * @param show_table_edu $page
     * @return string html for the page
     */
    public function render_show_table_edu(show_table_edu $page): string
    {
        $data = $page->export_for_template($this);
        return parent::render_from_template('tool_scoss/show_table_edu', $data);
    }

}
