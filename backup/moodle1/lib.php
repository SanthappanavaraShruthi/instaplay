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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 *
 * @package    mod_instaplay
 * @copyright  2023 Shruthi S
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * instaplay conversion handler. This resource handler is called by moodle1_mod_resource_handler
 */
class moodle1_mod_url_handler extends moodle1_resource_successor_handler {

    /** @var moodle1_file_manager instance */
    protected $fileman = null;

    /**
     * Converts /MOODLE_BACKUP/COURSE/MODULES/MOD/RESOURCE data
     * Called by moodle1_mod_resource_handler::process_resource()
     */
    public function process_legacy_resource(array $data, array $raw = null) {

        // Get the course module id and context id.
        $instanceid = $data['id'];
        $cminfo     = $this->get_cminfo($instanceid, 'resource');
        $moduleid   = $cminfo['id'];
        $contextid  = $this->converter->get_contextid(CONTEXT_MODULE, $moduleid);

        // Prepare the new instaplay instance record.
        $instaplay                 = array();
        $instaplay['id']           = $data['id'];
        $instaplay['name']         = $data['name'];
        $instaplay['intro']        = $data['intro'];
        $instaplay['introformat']  = $data['introformat'];
        $instaplay['playbackurl']  = $data['reference'];
        $instaplay['timemodified'] = $data['timemodified'];

        // Populate display and displayoptions fields.
        $options = array('printintro' => 1);
        if ($data['options'] == 'frame') {
            $instaplay['display'] = RESOURCELIB_DISPLAY_FRAME;
        } else if ($data['options'] == 'objectframe') {
            $instaplay['display'] = RESOURCELIB_DISPLAY_EMBED;
        } else if ($data['popup']) {
            $instaplay['display'] = RESOURCELIB_DISPLAY_POPUP;
            $rawoptions = explode(',', $data['popup']);
            foreach ($rawoptions as $rawoption) {
                list($name, $value) = explode('=', trim($rawoption), 2);
                if ($value > 0 && ($name == 'width' || $name == 'height')) {
                    $options['popup' . $name] = $value;
                    continue;
                }
            }
        } else {
            $instaplay['display'] = RESOURCELIB_DISPLAY_AUTO;
        }
        $instaplay['displayoptions'] = serialize($options);

        // Populate the parameters field.
        $parameters = array();
        if ($data['alltext']) {
            $rawoptions = explode(',', $data['alltext']);
            foreach ($rawoptions as $rawoption) {
                list($variable, $parameter) = explode('=', trim($rawoption), 2);
                $parameters[$parameter] = $variable;
            }
        }
        $instaplay['parameters'] = serialize($parameters);

        // Convert course files embedded into the intro.
        $this->fileman = $this->converter->get_file_manager($contextid, 'mod_instaplay', 'intro');
        $instaplay['intro'] = moodle1_converter::migrate_referenced_files($instaplay['intro'], $this->fileman);

        // Write instaplay.xml.
        $this->open_xml_writer("activities/instaplay_{$moduleid}/instaplay.xml");
        $this->xmlwriter->begin_tag('activity', array(
            'id' => $instanceid, 'moduleid' => $moduleid,
            'modulename' => 'instaplay', 'contextid' => $contextid
        ));
        $this->write_xml('instaplay', $instaplay, array('/instaplay/id'));
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

        // Write inforef.xml.
        $this->open_xml_writer("activities/instaplay_{$moduleid}/inforef.xml");
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->fileman->get_fileids() as $fileid) {
            $this->write_xml('file', array('id' => $fileid));
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();
    }
}
