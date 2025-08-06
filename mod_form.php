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
 * Page configuration form
 *
 * @package mod_nedpageplus
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/nedpageplus/locallib.php');
require_once($CFG->libdir.'/filelib.php');

class mod_nedpageplus_mod_form extends moodleform_mod {
    /**
     * Form definition
     * @noinspection PhpOverridingMethodVisibilityInspection
     */
    public function definition(){
        global $CFG, $DB;

        $mform = $this->_form;

        $config = get_config('nedpageplus');

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => '48']);
        if (!empty($CFG->formatstringstriptags)){
            $mform->setType('name', PARAM_TEXT);
        } else{
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();

        //-------------------------------------------------------
        $mform->addElement('header', 'contentsection', get_string('contentheader', 'nedpageplus'));
        $mform->addElement('editor', 'nedpageplus', get_string('content', 'nedpageplus'), null,
            nedpageplus_get_editor_options($this->context));
        $mform->addRule('nedpageplus', get_string('required'), 'required', null, 'client');

        //-------------------------------------------------------
        $mform->addElement('header', 'fileattachmentsection', get_string('fileattachment', 'nedpageplus'));

        $filemanager_options = [];
        $filemanager_options['accepted_types'] = '*';
        $filemanager_options['maxbytes'] = 0;
        $filemanager_options['maxfiles'] = 1;
        $filemanager_options['mainfile'] = true;

        $mform->addElement('filemanager', 'files', get_string('file', 'nedpageplus'), null, $filemanager_options);

        $mform->addElement('text', 'linkname', get_string('linkname', 'nedpageplus'), ['size' => '48']);
        if (!empty($CFG->formatstringstriptags)){
            $mform->setType('linkname', PARAM_TEXT);
        } else{
            $mform->setType('linkname', PARAM_CLEANHTML);
        }
        $mform->addRule('linkname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $linkpositions = [
            NEDPAGEPLUS_TOP    => get_string('top', 'nedpageplus'),
            NEDPAGEPLUS_BOTTOM => get_string('bottom', 'nedpageplus'),
            NEDPAGEPLUS_BOTH   => get_string('topbottom', 'nedpageplus'),
        ];

        $mform->addElement('select', 'linkposition', get_string('linkposition', 'nedpageplus'), $linkpositions);

        //-------------------------------------------------------
        $mform->addElement('header', 'fileappearancehdr', get_string('attachmentbehaviour', 'nedpageplus'));

        $fileoptions = [
            RESOURCELIB_DISPLAY_DOWNLOAD => get_string('resourcedisplaydownload'),
            RESOURCELIB_DISPLAY_OPEN     => get_string('resourcedisplayopen'),
            RESOURCELIB_DISPLAY_POPUP    => get_string('resourcedisplaypopup'),
        ];

        $mform->addElement('select', 'filedisplay', get_string('displayselect', 'nedpageplus'), $fileoptions);
        $mform->setDefault('filedisplay', $config->filedisplay);
        $mform->addHelpButton('filedisplay', 'displayselect', 'nedpageplus');

        $mform->addElement('text', 'filepopupwidth', get_string('popupwidth', 'nedpageplus'), ['size' => 3]);
        if (count($fileoptions) > 1){
            $mform->disabledIf('filepopupwidth', 'filedisplay', 'noteq', RESOURCELIB_DISPLAY_POPUP);
        }
        $mform->setType('filepopupwidth', PARAM_INT);
        $mform->setDefault('filepopupwidth', $config->filepopupwidth);

        $mform->addElement('text', 'filepopupheight', get_string('popupheight', 'nedpageplus'), ['size' => 3]);
        if (count($fileoptions) > 1){
            $mform->disabledIf('filepopupheight', 'filedisplay', 'noteq', RESOURCELIB_DISPLAY_POPUP);
        }
        $mform->setType('filepopupheight', PARAM_INT);
        $mform->setDefault('filepopupheight', $config->filepopupheight);

        $mform->addElement('advcheckbox', 'fileprintheading', get_string('printheading', 'nedpageplus'));
        $mform->setDefault('fileprintheading', $config->fileprintheading);
        $mform->addElement('advcheckbox', 'fileprintintro', get_string('printintro', 'nedpageplus'));
        $mform->setDefault('fileprintintro', $config->fileprintintro);

        //-------------------------------------------------------
        $mform->addElement('header', 'appearancehdr', get_string('pagebehaviour', 'nedpageplus'));

        $defaultdisplayoptions = [RESOURCELIB_DISPLAY_OPEN, RESOURCELIB_DISPLAY_POPUP];

        if ($this->current->instance){
            $options = resourcelib_get_displayoptions($defaultdisplayoptions, $this->current->display);
        } else{
            $options = resourcelib_get_displayoptions($defaultdisplayoptions);
        }
        if (count($options) == 1){
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else{
            $mform->addElement('select', 'display', get_string('displayselect', 'nedpageplus'), $options);
            $mform->setDefault('display', $config->display);
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)){
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'nedpageplus'), ['size' => 3]);
            if (count($options) > 1){
                $mform->disabledIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'nedpageplus'), ['size' => 3]);
            if (count($options) > 1){
                $mform->disabledIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
        }

        $mform->addElement('advcheckbox', 'printheading', get_string('printheading', 'nedpageplus'));
        $mform->setDefault('printheading', $config->printheading);
        $mform->addElement('advcheckbox', 'printintro', get_string('printintro', 'nedpageplus'));
        $mform->setDefault('printintro', $config->printintro);

        // add legacy files flag only if used
        if (isset($this->current->legacyfiles) and $this->current->legacyfiles != RESOURCELIB_LEGACYFILES_NO){
            $options = [
                RESOURCELIB_LEGACYFILES_DONE   => get_string('legacyfilesdone', 'nedpageplus'),
                RESOURCELIB_LEGACYFILES_ACTIVE => get_string('legacyfilesactive', 'nedpageplus'),
            ];
            $mform->addElement('select', 'legacyfiles', get_string('legacyfiles', 'nedpageplus'), $options);
            $mform->setAdvanced('legacyfiles', 1);
        }

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();

        //-------------------------------------------------------
        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
    }

    public function data_preprocessing(&$default_values){
        if ($this->current->instance){
            $draftitemid = file_get_submitted_draft_itemid('nedpageplus');
            $default_values['nedpageplus']['format'] = $default_values['contentformat'];
            $default_values['nedpageplus']['text'] =
                file_prepare_draft_area($draftitemid, $this->context->id, 'mod_nedpageplus', 'content', 0,
                    nedpageplus_get_editor_options($this->context), $default_values['content']);
            $default_values['nedpageplus']['itemid'] = $draftitemid;
        }
        // File options.
        if (!empty($default_values['filedisplayoptions'])){
            $filedisplayoptions = unserialize($default_values['filedisplayoptions']);
            if (isset($filedisplayoptions['fileprintintro'])){
                $default_values['fileprintintro'] = $filedisplayoptions['fileprintintro'];
            }
            if (isset($filedisplayoptions['fileprintheading'])){
                $default_values['fileprintheading'] = $filedisplayoptions['fileprintheading'];
            }
            if (!empty($filedisplayoptions['filepopupwidth'])){
                $default_values['filepopupwidth'] = $filedisplayoptions['filepopupwidth'];
            }
            if (!empty($filedisplayoptions['filepopupheight'])){
                $default_values['filepopupheight'] = $filedisplayoptions['filepopupheight'];
            }
        }
        // Page options.
        if (!empty($default_values['displayoptions'])){
            $displayoptions = unserialize($default_values['displayoptions']);
            if (isset($displayoptions['printintro'])){
                $default_values['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printheading'])){
                $default_values['printheading'] = $displayoptions['printheading'];
            }
            if (!empty($displayoptions['popupwidth'])){
                $default_values['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])){
                $default_values['popupheight'] = $displayoptions['popupheight'];
            }
        }

        if ($this->current->instance){
            $draftitemid = file_get_submitted_draft_itemid('files');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_nedpageplus', 'attachment', 0, ['subdirs' => true]);
            $default_values['files'] = $draftitemid;
        }
    }
}

