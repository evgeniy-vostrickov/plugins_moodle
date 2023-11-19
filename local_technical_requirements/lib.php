<?php

function local_technical_requirements_extend_navigation_user($navigation, $user, $context, $course, $coursecontext) {
    $url = new moodle_url('/local/technical_requirements/index.php');
    $navigation->add(get_string('mintechrequirements', 'local_technical_requirements'), $url);
}

function local_technical_requirements_standard_footer_html() {
    $output = '';

    $url = new moodle_url('/local/technical_requirements/index.php');
    $output = html_writer::link($url, get_string('mintechrequirements', 'local_technical_requirements'));
    $output = html_writer::div($output, 'tool_dataprivacy');
    
    return $output;
}