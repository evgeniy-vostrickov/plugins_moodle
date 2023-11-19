<?php

/**
 * Renderer class for local_technical_requirements
 *
 * @package    local_technical_requirements
 */

namespace local_technical_requirements\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use stdClass;

/**
 * Renderer class for competency statistic report
 *
 * @package    local_technical_requirements
 */
class renderer extends plugin_renderer_base {

    /**
     * @param technical_requirements $page
     * @return string html for the page
     */
    public function render_technical_requirements(technical_requirements $page): string
    {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_technical_requirements/technical_requirements', $data);
    }

}
