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
 * Resource module version information
 *
 * @package    mod_nedpageplus
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/nedpageplus/lib.php');
require_once($CFG->dirroot.'/mod/nedpageplus/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT); // Course Module ID
$r        = optional_param('r', 0, PARAM_INT);  // Resource instance ID
$redirect = optional_param('redirect', 0, PARAM_BOOL);
$forceview = optional_param('forceview', 0, PARAM_BOOL);

if ($r) {
    if (!$nedpageplus = $DB->get_record('nedpageplus', array('id'=>$r))) {
        nedpageplus_redirect_if_migrated($r, 0);
        throw new \moodle_exception('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('nedpageplus', $nedpageplus->id, $nedpageplus->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('nedpageplus', $id)) {
        nedpageplus_redirect_if_migrated(0, $id);
        throw new \moodle_exception('invalidcoursemodule');
    }
    $nedpageplus = $DB->get_record('nedpageplus', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/nedpageplus:view', $context);

// Completion and trigger events.
nedpageplus_view($nedpageplus, $course, $cm, $context);

$PAGE->set_url('/mod/nedpageplus/view.php', array('id' => $cm->id));

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_nedpageplus', 'attachment', 0, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
if (count($files) < 1) {
    nedpageplus_print_filenotfound($nedpageplus, $cm, $course);
    die;
} else {
    $file = reset($files);
    unset($files);
}

$nedpageplus->mainfile = $file->get_filename();
$displaytype = nedpageplus_get_final_display_type($nedpageplus);
if ($displaytype == RESOURCELIB_DISPLAY_OPEN || $displaytype == RESOURCELIB_DISPLAY_DOWNLOAD) {
    $redirect = true;
}

// Don't redirect teachers, otherwise they can not access course or module settings.
if ($redirect && !course_get_format($course)->has_view_page() &&
        (has_capability('moodle/course:manageactivities', $context) ||
        has_capability('moodle/course:update', context_course::instance($course->id)))) {
    $redirect = false;
}

if ($redirect && !$forceview) {
    // coming from course page or url index page
    // this redirect trick solves caching problems when tracking views ;-)
    $path = '/'.$context->id.'/mod_nedpageplus/attachment/'.$nedpageplus->revision.$file->get_filepath().$file->get_filename();
    $fullurl = moodle_url::make_file_url('/pluginfile.php', $path, $displaytype == RESOURCELIB_DISPLAY_DOWNLOAD);
    redirect($fullurl);
}

switch ($displaytype) {
    case RESOURCELIB_DISPLAY_EMBED:
        nedpageplus_display_embed($nedpageplus, $cm, $course, $file);
        break;
    case RESOURCELIB_DISPLAY_FRAME:
        nedpageplus_display_frame($nedpageplus, $cm, $course, $file);
        break;
    default:
        nedpageplus_print_workaround($nedpageplus, $cm, $course, $file);
        break;
}