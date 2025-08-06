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
 * Page module version information
 *
 * @package mod_nedpageplus
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/nedpageplus/lib.php');
require_once($CFG->dirroot.'/mod/nedpageplus/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$p       = optional_param('p', 0, PARAM_INT);  // Page instance ID
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);

if ($p){
    if (!$page = $DB->get_record('nedpageplus', ['id' =>$p])){
        throw new \moodle_exception('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('nedpageplus', $page->id, $page->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('nedpageplus', $id)){
        throw new \moodle_exception('invalidcoursemodule');
    }
    $page = $DB->get_record('nedpageplus', ['id' =>$cm->instance], '*', MUST_EXIST);
}

$course = $DB->get_record('course', ['id' =>$cm->course], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/nedpageplus:view', $context);

// Completion and trigger events.
nedpageplus_view($page, $course, $cm, $context);

$PAGE->set_url('/mod/nedpageplus/view.php', ['id' => $cm->id]);

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_nedpageplus', 'attachment', 0, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
$filelink = null;
if ($files){
    $file = reset($files);
    unset($files);

    $icon = $OUTPUT->image_url(file_file_icon($file))->out(false);

    $displaytype = nedpageplus_get_final_display_type($page);
    if ($displaytype == RESOURCELIB_DISPLAY_POPUP){
        $path = '/'.$file->get_contextid().'/mod_nedpageplus/attachment/'.$page->revision.$file->get_filepath().$file->get_filename();
        $fullurl = \moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
        $options = empty($page->filedisplayoptions) ? [] : unserialize($page->filedisplayoptions);
        $width  = empty($options['filepopupwidth'])  ? 620 : $options['filepopupwidth'];
        $height = empty($options['filepopupheight']) ? 450 : $options['filepopupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $extra = "onclick=\"window.open('$fullurl', '', '$wh'); return false;\"";
        //$filelink = html_writer::img($icon, '') . ' ' . "<a href=\"$fullurl\" $extra>".($page->linkname) ? $page->linkname : $file->get_filename()."</a>";

        $filelink = html_writer::img($icon, '') . ' ' .
            html_writer::link(
                $fullurl,
                ($page->linkname) ?: $file->get_filename(),
                [
                    'onclick' => "window.open('$fullurl', '', '$wh'); return false;"
                ]
            );
    } else {
        $filelink = html_writer::img($icon, '') . ' ' .
            html_writer::link(
                new moodle_url('/mod/nedpageplus/attachment.php', ['id' => $id]),
                $page->linkname ?: $file->get_filename()
            );
    }
}

$options = empty($page->displayoptions) ? [] : unserialize($page->displayoptions);

if ($inpopup and $page->display == RESOURCELIB_DISPLAY_POPUP){
    $PAGE->set_pagelayout('popup');
    $PAGE->set_title($course->shortname.': '.$page->name);
    $PAGE->set_heading($course->fullname);
} else {
    $PAGE->set_title($course->shortname.': '.$page->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($page);
}
echo $OUTPUT->header();
if (!isset($options['printheading']) || !empty($options['printheading'])){
    echo $OUTPUT->heading(format_string($page->name), 2);
}

if (!empty($options['printintro'])){
    if (trim(strip_tags($page->intro))){
        echo $OUTPUT->box_start('mod_introbox', 'pageintro');
        echo format_module_intro('nedpageplus', $page, $cm->id);
        echo $OUTPUT->box_end();
    }
}

$content = file_rewrite_pluginfile_urls($page->content, 'pluginfile.php', $context->id, 'mod_nedpageplus', 'content', $page->revision);
$formatoptions = new stdClass();
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $context;
$content = format_text($content, $page->contentformat, $formatoptions);

if ($filelink && ($page->linkposition == NEDPAGEPLUS_TOP || $page->linkposition == NEDPAGEPLUS_BOTH)){
    echo html_writer::div(
        html_writer::div($filelink, 'attachment-wrapper-top'),
        'attachment-wrapper-top'
    );
}

echo $OUTPUT->box($content, "generalbox center clearfix");

if ($filelink && ($page->linkposition == NEDPAGEPLUS_BOTTOM || $page->linkposition == NEDPAGEPLUS_BOTH)){
    echo html_writer::div(
        html_writer::div($filelink, ''),
        'attachment-wrapper-bottom'
    );
}

$strlastmodified = get_string("lastmodified");
echo "<div class=\"modified\">$strlastmodified: ".userdate($page->timemodified)."</div>";

echo $OUTPUT->footer();
