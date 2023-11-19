<?php

// function local_individual_trajectory_extend_navigation($navigation, $course, $context) {
//     $url = new moodle_url('/local/individual_trajectory/index.php');
//     $navigation->add(get_string('individuallearning', 'local_individual_trajectory'), $url);
// }

function local_individual_trajectory_extend_navigation_user($navigation, $user, $context, $course, $coursecontext) {
    $url = new moodle_url('/local/individual_trajectory/index.php');
    $navigation->add(get_string('individuallearning', 'local_individual_trajectory'), $url);
}

function local_individual_trajectory_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    $individuallearning = new moodle_url('/local/individual_trajectory/index.php');
    $node = new core_user\output\myprofile\node('miscellaneous', 'individual_trajectory', get_string('individuallearning', 'local_individual_trajectory'), null, $individuallearning);
    $tree->add_node($node);

    $profstandards = new moodle_url('/local/individual_trajectory/prof_standards.php');
    $node = new core_user\output\myprofile\node('miscellaneous', 'prof_standards', get_string('profstandards', 'local_individual_trajectory'), null, $profstandards);
    $tree->add_node($node);
    return true;
}