<?php
/**
 *
 * @package    local
 * @subpackage local_technical_requirements
 */

use stdClass;
use core_completion_external;

require_once('../../config.php');

$baseurl = new moodle_url('/local/technical_requirements/index.php');

$PAGE->requires->css(new moodle_url('/local/technical_requirements/src/style.css'));
$PAGE->set_title(get_string('mintechrequirements', 'local_technical_requirements'));
$PAGE->set_heading(get_string('mintechrequirements', 'local_technical_requirements'));
echo $OUTPUT->header();

$output = $PAGE->get_renderer('local_technical_requirements');
$page = new \local_technical_requirements\output\technical_requirements();
echo $output->render($page);

echo $OUTPUT->footer();