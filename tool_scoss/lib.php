<?php

function tool_scoss_extend_navigation_user($navigation, $user, $context, $course, $coursecontext) {
    $url = new moodle_url('admin/tool/scoss/index.php');
    $navigation->add(get_string('scoss', 'tool_scoss'), $url);
}

function tool_scoss_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    return true;
}