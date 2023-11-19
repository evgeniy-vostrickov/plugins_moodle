<?php

/**
 * Renderer class for local_individual_trajectory
 *
 * @package    local_individual_trajectory
 */

namespace local_individual_trajectory\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use stdClass;

/**
 * Renderer class for competency statistic report
 *
 * @package    local_individual_trajectory
 */
class renderer extends plugin_renderer_base {

    /**
     * @param construction_learning_path $page
     * @return string html for the page
     */
    public function render_construction_learning_path(construction_learning_path $page): string
    {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_individual_trajectory/construction_learning_path', $data);
    }
    /**
     * @param construction_prof_standards $page
     * @return string html for the page
     */
    public function render_construction_prof_standards(construction_prof_standards $page): string
    {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_individual_trajectory/construction_prof_standards', $data);
    }
    /**
     * @param compiled_individual_learning $page
     * @return string html for the page
     */
    public function render_compiled_individual_learning(compiled_individual_learning $page): string
    {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_individual_trajectory/compiled_individual_learning', $data);
    }

}
