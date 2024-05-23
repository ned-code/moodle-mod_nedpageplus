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
 * Private page module utility functions
 *
 * @package mod_nedpageplus
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

define('NEDPAGEPLUS_TOP', 1);
define('NEDPAGEPLUS_BOTTOM', 2);
define('NEDPAGEPLUS_BOTH', 3);

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/nedpageplus/lib.php");


/**
 * File browsing support class
 */
class nedpageplus_content_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}

function nedpageplus_get_editor_options($context) {
    global $CFG;
    return array('subdirs'=>1, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>-1, 'changeformat'=>1, 'context'=>$context, 'noclean'=>1, 'trusttext'=>0);
}

function nedpageplus_set_mainfile($data) {
    global $DB;
    $fs = get_file_storage();
    $cmid = $data->coursemodule;
    $draftitemid = $data->files;

    $context = context_module::instance($cmid);
    if ($draftitemid) {
        $options = array(
            'subdirs' => true,
            'embed' => false
        );
        if ($data->display == RESOURCELIB_DISPLAY_EMBED) {
            $options['embed'] = true;
        }
        file_save_draft_area_files($draftitemid, $context->id, 'mod_nedpageplus', 'attachment', 0, $options);
    }
    $files = $fs->get_area_files($context->id, 'mod_nedpageplus', 'attachment', 0, 'sortorder', false);
    if (count($files) == 1) {
        // only one file attached, set it as main file automatically
        $file = reset($files);
        file_set_sortorder($context->id, 'mod_nedpageplus', 'attachment', 0, $file->get_filepath(), $file->get_filename(), 1);
    }
}

/**
 * Decide the best display format.
 * @param object $resource
 * @return int display type constant
 */
function nedpageplus_get_final_display_type($resource) {
    global $CFG, $PAGE;

    if ($resource->filedisplay != RESOURCELIB_DISPLAY_AUTO) {
        return $resource->filedisplay;
    }

    if (empty($resource->mainfile)) {
        return RESOURCELIB_DISPLAY_DOWNLOAD;
    } else {
        $mimetype = mimeinfo('type', $resource->mainfile);
    }

    if (file_mimetype_in_typegroup($mimetype, 'archive')) {
        return RESOURCELIB_DISPLAY_DOWNLOAD;
    }
    if (file_mimetype_in_typegroup($mimetype, array('web_image', '.htm', 'web_video', 'web_audio'))) {
        return RESOURCELIB_DISPLAY_EMBED;
    }

    // let the browser deal with it somehow
    return RESOURCELIB_DISPLAY_OPEN;
}

/**
 * Print resource info and workaround link when JS not available.
 * @param object $resource
 * @param object $cm
 * @param object $course
 * @param stored_file $file main file
 */
function nedpageplus_print_workaround($resource, $cm, $course, $file) {
    global $CFG, $OUTPUT;

    nedpageplus_print_header($resource, $cm, $course);
    nedpageplus_print_heading($resource, $cm, $course, true);
    nedpageplus_print_intro($resource, $cm, $course, true);

    $resource->mainfile = $file->get_filename();
    echo '<div class="resourceworkaround">';
    switch (nedpageplus_get_final_display_type($resource)) {
        case RESOURCELIB_DISPLAY_POPUP:
            $path = '/'.$file->get_contextid().'/mod_nedpageplus/attachment/'.$resource->revision.$file->get_filepath().$file->get_filename();
            $fullurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);
            $options = empty($resource->filedisplayoptions) ? array() : unserialize($resource->filedisplayoptions);
            $width  = empty($options['filepopupwidth'])  ? 620 : $options['filepopupwidth'];
            $height = empty($options['filepopupheight']) ? 450 : $options['filepopupheight'];
            $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
            $extra = "onclick=\"window.open('$fullurl', '', '$wh'); return false;\"";
            echo nedpageplus_get_clicktoopen($file, $resource->revision, $extra);
            break;

        case RESOURCELIB_DISPLAY_NEW:
            $extra = 'onclick="this.target=\'_blank\'"';
            echo nedpageplus_get_clicktoopen($file, $resource->revision, $extra);
            break;

        case RESOURCELIB_DISPLAY_DOWNLOAD:
            echo nedpageplus_get_clicktodownload($file, $resource->revision);
            break;

        case RESOURCELIB_DISPLAY_OPEN:
        default:
            echo nedpageplus_get_clicktoopen($file, $resource->revision);
            break;
    }
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}
/**
 * Print nedpageplus header.
 * @param object $nedpageplus
 * @param object $cm
 * @param object $course
 * @return void
 */
function nedpageplus_print_header($nedpageplus, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$nedpageplus->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($nedpageplus);
    echo $OUTPUT->header();
}

/**
 * Print nedpageplus heading.
 * @param object $nedpageplus
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used
 * @return void
 */
function nedpageplus_print_heading($nedpageplus, $cm, $course, $notused = false) {
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($nedpageplus->name), 2);
}

/**
 * Print nedpageplus introduction.
 * @param object $nedpageplus
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function nedpageplus_print_intro($nedpageplus, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($nedpageplus->displayoptions) ? array() : unserialize($nedpageplus->displayoptions);

    $extraintro = nedpageplus_get_optional_details($nedpageplus, $cm);
    if ($extraintro) {
        // Put a paragaph tag around the details
        $extraintro = html_writer::tag('p', $extraintro, array('class' => 'resourcedetails'));
    }

    if ($ignoresettings || !empty($options['printintro']) || $extraintro) {
        $gotintro = trim(strip_tags($nedpageplus->intro));
        if ($gotintro || $extraintro) {
            echo $OUTPUT->box_start('mod_introbox', 'resourceintro');
            if ($gotintro) {
                echo format_module_intro('nedpageplus', $nedpageplus, $cm->id);
            }
            echo $extraintro;
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Gets optional details for a nedpageplus, depending on nedpageplus settings.
 *
 * Result may include the file size and type if those settings are chosen,
 * or blank if none.
 *
 * @param object $nedpageplus nedpageplus table row (only property 'displayoptions' is used here)
 * @param object $cm Course-module table row
 * @return string Size and type or empty string if show options are not enabled
 */
function nedpageplus_get_optional_details($nedpageplus, $cm) {
    global $DB;

    $details = '';

    $options = empty($nedpageplus->displayoptions) ? array() : @unserialize($nedpageplus->displayoptions);
    if (!empty($options['showsize']) || !empty($options['showtype']) || !empty($options['showdate'])) {
        if (!array_key_exists('filedetails', $options)) {
            $filedetails = nedpageplus_get_file_details($nedpageplus, $cm);
        } else {
            $filedetails = $options['filedetails'];
        }
        $size = '';
        $type = '';
        $date = '';
        $langstring = '';
        $infodisplayed = 0;
        if (!empty($options['showsize'])) {
            if (!empty($filedetails['size'])) {
                $size = display_size($filedetails['size']);
                $langstring .= 'size';
                $infodisplayed += 1;
            }
        }
        if (!empty($options['showtype'])) {
            if (!empty($filedetails['type'])) {
                $type = $filedetails['type'];
                $langstring .= 'type';
                $infodisplayed += 1;
            }
        }
        if (!empty($options['showdate']) && (!empty($filedetails['modifieddate']) || !empty($filedetails['uploadeddate']))) {
            if (!empty($filedetails['modifieddate'])) {
                $date = get_string('modifieddate', 'mod_nedpageplus', userdate($filedetails['modifieddate'],
                    get_string('strftimedatetimeshort', 'langconfig')));
            } else if (!empty($filedetails['uploadeddate'])) {
                $date = get_string('uploadeddate', 'mod_nedpageplus', userdate($filedetails['uploadeddate'],
                    get_string('strftimedatetimeshort', 'langconfig')));
            }
            $langstring .= 'date';
            $infodisplayed += 1;
        }

        if ($infodisplayed > 1) {
            $details = get_string("resourcedetails_{$langstring}", 'nedpageplus',
                (object)array('size' => $size, 'type' => $type, 'date' => $date));
        } else {
            // Only one of size, type and date is set, so just append.
            $details = $size . $type . $date;
        }
    }

    return $details;
}

/**
 * Internal function - create click to open text with link.
 */
function nedpageplus_get_clicktoopen($file, $revision, $extra='') {
    global $CFG;

    $filename = $file->get_filename();
    $path = '/'.$file->get_contextid().'/mod_nedpageplus/attachment/'.$revision.$file->get_filepath().$file->get_filename();
    $fullurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);

    $string = get_string('clicktoopen2', 'nedpageplus', "<a href=\"$fullurl\" $extra>$filename</a>");

    return $string;
}
