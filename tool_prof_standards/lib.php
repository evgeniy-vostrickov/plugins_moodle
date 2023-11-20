<?php

function tool_prof_standards_extend_navigation_user($navigation, $user, $context, $course, $coursecontext) {
    $url = new moodle_url('admin/tool/prof_standards/index.php');
    $navigation->add(get_string('profstandards', 'tool_prof_standards'), $url);
}

function tool_prof_standards_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    return true;
}