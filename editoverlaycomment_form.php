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
 * Form for editing a users profile
 *
 * @copyright Shruthi S
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package instaplay_comments
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/mod/instaplay/locallib.php');

class instaplay_editoverlaycomment_form extends moodleform {

    /** @var array caches the options for checkbox */
    protected $_options;

    /**
     * Define the form.
     */
    public function definition()
    {
        global  $DB;
        $mform = $this->_form;

        if (!is_array($this->_customdata)) {
            throw new coding_exception('invalid custom data for edit_overlay_comment_form');
        }

        $overlaycomment = $this->_customdata['overlaycomment'];

        $overlaycommentid = $overlaycomment->id;

        $strgeneral  = get_string('general');
        $mform->addElement('header', 'moodle', $strgeneral);

        // Add some extra hidden fields.
        $mform->addElement('hidden', 'id', $overlaycomment->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'starttime', "Start time", 'size="20"');
        $mform->addHelpButton('starttime', 'starttime', 'instaplay');
        $mform->setType('starttime', PARAM_RAW);
        $mform->addRule('starttime', null, 'required', null, 'client');
        $mform->addRule('starttime', null, 'numeric', null, 'client');

        $mform->addElement('text', 'endtime', "End time");
        $mform->addHelpButton('endtime', 'endtime', 'instaplay');
        $mform->setType('endtime', PARAM_RAW);
        $mform->addRule('endtime', null, 'required', null, 'client');
        $mform->addRule('endtime', null, 'numeric', null, 'client');

        $positions = mod_instaplay\instaplay_overlay::get_position_list();
        $mform->addElement('select', 'position', "Position", $positions);
        $mform->addHelpButton('position', 'position', 'instaplay');
        $mform->setType('position', PARAM_RAW);
        $mform->addRule('position', null, 'required', null, 'client');

        $types = mod_instaplay\instaplay_overlay::get_type_list();
        $mform->addElement('select', 'type', "Type", $types);
        $mform->addHelpButton('type', 'type', 'instaplay');
        $mform->setType('type', PARAM_RAW);
        $mform->addRule('type', null, 'required', null, 'client');

        // Comment fields.
        $mform->addElement('text', 'comment', "Comment");
        $mform->addHelpButton('comment', 'comment', 'instaplay');
        $mform->setType('comment', PARAM_RAW);
        $mform->hideIf('comment', 'type', 'noteq', 0);


        // InfoToast fields.
        $mform->addElement('text', 'infoTosttitle', "Title", array('size'=>70));
        $mform->addHelpButton('infoTosttitle', 'infoTosttitle', 'instaplay');
        $mform->setType('infoTosttitle', PARAM_RAW);
        $mform->hideIf('infoTosttitle', 'type', 'noteq', 2);

        /* $mform->addElement('text', 'body', "Title");
        $mform->addHelpButton('body', 'body', 'instaplay');
        $mform->setType('body', PARAM_RAW);
        $mform->hideIf('body', 'type', 'noteq', 2); */


        // RadioToast fields.
        $mform->addElement('text', 'title', "Title", array('size'=>70));
        $mform->addHelpButton('title', 'title', 'instaplay');
        $mform->setType('title', PARAM_RAW);
        $mform->hideIf('title', 'type', 'noteq', 1);

        $repeatarray = array();
        $repeateloptions = array();
        $repeatarray[] = $mform->createElement('text', 'option', get_string('option', 'instaplay'), ['size' => 30]);
        $repeatarray[] = $mform->createElement('hidden', 'optionid', 0);
        $repeateloptions['option']['hideIf'] = array('type', 'eq', 1);
        $repeateloptions['option']['type'] = PARAM_RAW;
        $mform->setType('optionid', PARAM_INT);

        // Noumber of options in the database for edit
        $this->_options = $DB->get_records('instaplay_overlay_options', ['instaplayoverlayid' => $overlaycommentid]);
        $numoptions = count($this->_options);

        if ($numoptions > 0) {
            $numoptions = count($this->_options);
        } else {
            $numoptions = 1;
        }
        $numoptions = max($numoptions, 2);
        $nextel = $this->repeat_elements($repeatarray, $numoptions, $repeateloptions, 'option_repeats',
            'option_add_fields', 1, get_string('addmoreoptions', 'instaplay'), true);


        for ($i = 0; $i < $nextel; $i++) {
            $mform->hideIf('option[' . $i . ']', 'type', 'noteq', 1);
            $mform->hideIf('option_add_fields', 'type', 'noteq', 1);
        }


        if ($overlaycommentid == -1) {
            $btnstring = get_string('save', 'instaplay');
        } else {
            $btnstring = get_string('update', 'instaplay');
        }
        $this->add_action_buttons(true, $btnstring);

        $this->set_data($overlaycomment);
    }
}
