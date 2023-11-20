<?php

/**
 * Renderer class for tool_prof_standards
 *
 * @package    tool_prof_standards
 */

namespace tool_prof_standards\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use stdClass;

/**
 * Renderer class for competency statistic report
 *
 * @package    tool_prof_standards
 */
class renderer extends plugin_renderer_base {

    /**
     * @param add_new_prof_standards $page
     * @return string html for the page
     */
    public function render_add_new_prof_standards(add_new_prof_standards $page): string
    {
        $data = $page->export_for_template($this);
        return parent::render_from_template('tool_prof_standards/add_new_prof_standards', $data);
    }

}
