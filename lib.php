<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Lib for CompetVetEval
 *
 * @package   local_cveteval
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cveteval\roles;
use local_cveteval\utils;

/**
 * Get plugin file
 *
 * @param object $course
 * @param object $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return false|void
 * @throws coding_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function local_cveteval_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }
    // Make sure the user is logged in and has access to the module
    // (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);

    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    if (!has_capability('local/cveteval:viewfiles', $context)) {
        return false;
    }

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_cveteval', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

/**
 * Nothing for now
 */
function local_cveteval_enable_disable_plugin_callback() {
    $enabled = $CFG->enablecompetveteval ?? false;
    utils::setup_mobile_service($enabled);
}

/**
 * This function extends the user navigation.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $user The user object
 * @param context_user $usercontext The user context
 * @param stdClass $course The course object
 * @param context_course $coursecontext The context of the course
 */
function local_cveteval_extend_navigation_user($navigation, $user, $usercontext, $course, $coursecontext) {
    $node = utils::get_assessment_node();
    if ($node) {
        $navigation->add_node($node);
    }
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 *
 * @return bool
 */
function local_cveteval_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    $node = utils::get_assessment_node();
    if ($node) {
        $treenode = new core_user\output\myprofile\node('miscellaneous', 'cvetevaleval',
            $node->text, null, $node->action);
        $tree->add_node($treenode);
        return true;
    }
    return false;
}

/**
 * Extends frontpage navigation node
 *
 * @param navigation_node $parentnode
 * @param stdClass $course
 * @param context_course $context
 */
function local_cveteval_extend_navigation_frontpage(
    navigation_node $parentnode,
    stdClass $course,
    context_course $context
) {
    $node = utils::get_assessment_node();
    if ($node) {
        $parentnode->add_node($node);
    }
}

/**
 * Extends navigation for course
 *
 * @param global_navigation $nav
 * @throws coding_exception
 * @throws dml_exception
 */
function local_cveteval_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    $node = utils::get_assessment_node();
    if ($node) {
        $parentnode->add_node($node);
    }
}
