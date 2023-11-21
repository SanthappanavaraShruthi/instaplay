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
 * Define all the backup steps that will be used by the backup_url_activity_task
 *
 * @package    mod_instaplay
 * @copyright  2010 onwards Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

 /**
  * Define the complete instaplay structure for backup, with file and id annotations
  */
class backup_instaplay_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // The instaplay module stores no user info.

        // Define each element separated.
        $instaplay = new backup_nested_element('instaplay', array('id'), array(
            'name', 'intro', 'introformat', 'videotitle', 'playbackurl',
            'catalogid', 'contentid', 'posterurl', 'timemodified'));

        // Build the tree.
        // Nothing here for instaplay.

        // Define sources.
        $instaplay->set_source_table('instaplay', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations.
        // Module has no id annotations.

        // Define file annotations.
        $instaplay->annotate_files('mod_instaplay', 'intro', null); // This file area hasn't itemid.

        // Return the root element (instaplay), wrapped into standard activity structure.
        return $this->prepare_activity_structure($instaplay);

    }
}
