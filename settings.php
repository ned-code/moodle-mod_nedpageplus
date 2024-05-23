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
 * Page module admin settings and defaults
 *
 * @package mod_nedpageplus
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $ADMIN;
if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_OPEN, RESOURCELIB_DISPLAY_POPUP));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_OPEN);

    // Attachment
    $settings->add(new admin_setting_heading('attachmentdefaults', get_string('attachmentbehaviour', 'nedpageplus'), ''));

    $settings->add(new admin_setting_configcheckbox('nedpageplus/fileprintheading',
        get_string('printheading', 'nedpageplus'), get_string('printheadingexplain', 'nedpageplus'), 1));
    $settings->add(new admin_setting_configcheckbox('nedpageplus/fileprintintro',
        get_string('printintro', 'nedpageplus'), get_string('printintroexplain', 'nedpageplus'), 0));
    $settings->add(new admin_setting_configselect('nedpageplus/filedisplay',
        get_string('displayselect', 'nedpageplus'), get_string('displayselectexplain', 'nedpageplus'), RESOURCELIB_DISPLAY_OPEN, $displayoptions));
    $settings->add(new admin_setting_configtext('nedpageplus/filepopupwidth',
        get_string('popupwidth', 'nedpageplus'), get_string('popupwidthexplain', 'nedpageplus'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('nedpageplus/filepopupheight',
        get_string('popupheight', 'nedpageplus'), get_string('popupheightexplain', 'nedpageplus'), 450, PARAM_INT, 7));

    // PAGE.
    $settings->add(new admin_setting_heading('pagedefaults', get_string('pagebehaviour', 'nedpageplus'), ''));

    $settings->add(new admin_setting_configcheckbox('nedpageplus/printheading',
        get_string('printheading', 'nedpageplus'), get_string('printheadingexplain', 'nedpageplus'), 1));
    $settings->add(new admin_setting_configcheckbox('nedpageplus/printintro',
        get_string('printintro', 'nedpageplus'), get_string('printintroexplain', 'nedpageplus'), 0));
    $settings->add(new admin_setting_configselect('nedpageplus/display',
        get_string('displayselect', 'nedpageplus'), get_string('displayselectexplain', 'nedpageplus'), RESOURCELIB_DISPLAY_OPEN, $displayoptions));
    $settings->add(new admin_setting_configtext('nedpageplus/popupwidth',
        get_string('popupwidth', 'nedpageplus'), get_string('popupwidthexplain', 'nedpageplus'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('nedpageplus/popupheight',
        get_string('popupheight', 'nedpageplus'), get_string('popupheightexplain', 'nedpageplus'), 450, PARAM_INT, 7));
}
