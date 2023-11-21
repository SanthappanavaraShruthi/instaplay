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
 * Instapla configuration form
 *
 * @package    mod_instaplay
 * @copyright  2023 Shruthi S
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/instaplay/locallib.php');

class mod_instaplay_mod_form extends moodleform_mod
{
    public function definition()
    {
        global $CFG, $DB;
        $mform = $this->_form;

        $config = get_config('instaplay');

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'instaplay'), array('size' => '100'));
        $mform->addHelpButton('name', 'name', 'instaplay');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'videotitle', get_string('videotitle', 'instaplay'), array('size' => '100'));
        $mform->addHelpButton('videotitle', 'videotitle', 'instaplay');
        $mform->setType('videotitle', PARAM_TEXT);
        $mform->addRule('videotitle', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');


        $mform->addElement('filepicker', 'userfile', 'Upload Video');
        $mform->addHelpButton('userfile', 'userfile', 'instaplay');


        $mform->addElement('text', 'playbackurl', get_string('playbackurl', 'instaplay'), array('size' => '100'));
        $mform->addHelpButton('playbackurl', 'playbackurl', 'instaplay');
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('playbackurl', PARAM_TEXT);
        } else {
            $mform->setType('playbackurl', PARAM_CLEANHTML);
        }
        $mform->addRule('playbackurl', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'posterurl', get_string('posterurl', 'mod_instaplay'), array('size' => '100'));
        $mform->addHelpButton('posterurl', 'posterurl', 'instaplay');
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('posterurl', PARAM_TEXT);
        } else {
            $mform->setType('posterurl', PARAM_CLEANHTML);
        }
        $mform->addRule('posterurl', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');


        $mform->addElement('text', 'duration', get_string('duration', 'mod_instaplay'), array('size' => '100'));
        $mform->addHelpButton('duration', 'duration', 'instaplay');
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('duration', PARAM_TEXT);
        } else {
            $mform->setType('duration', PARAM_CLEANHTML);
        }
        $mform->addRule('duration', get_string('maximumchars', '', 10), 'maxlength', 10, 'client');



        $mform->addElement('text', 'catalogid', get_string('catalogid', 'instaplay'), array('size' => '100'));
        $mform->addHelpButton('catalogid', 'catalogid', 'instaplay');
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('catalogid', PARAM_TEXT);
        } else {
            $mform->setType('catalogid', PARAM_CLEANHTML);
        }
        $mform->addRule('catalogid', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'contentid', get_string('contentid', 'instaplay'), array('size' => '100'));
        $mform->addHelpButton('contentid', 'contentid', 'instaplay');
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('contentid', PARAM_TEXT);
        } else {
            $mform->setType('contentid', PARAM_CLEANHTML);
        }
        $mform->addRule('contentid', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();
        $element = $mform->getElement('introeditor');
        $attributes = $element->getAttributes();
        $attributes['rows'] = 5;
        $element->setAttributes($attributes);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}
