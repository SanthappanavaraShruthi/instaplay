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
 * Mandatory public API of url module
 *
 * @package    mod_instaplay
 * @copyright  2023 Shruthi S
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use mod_instaplay\instaplay_videoupload;

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in instaplay module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function instaplay_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;

        default:
            return null;
    }
}

/**
 * Add instaplay instance.
 * @param object $data
 * @param object $mform
 * @return int new instaplay instance id
 */
function instaplay_add_instance($data, $mform)
{
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/instaplay/locallib.php');

    $data->timemodified = time();

    $filename = $mform->get_new_filename('userfile');
    if (!empty($filename)) {
        $videoid = upload_file_to_S3_and_initiate_transcoding($mform);
        $data->videoid = $videoid;
    }

    $data->id = $DB->insert_record('instaplay', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'instaplay', $data->id, $completiontimeexpected);

    return $data->id;
}

function instaplay_get_editor_options($context)
{
    global $CFG;
    return array('subdirs' => false, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => -1, 'changeformat' => 1, 'context' => $context, 'noclean' => 1, 'trusttext' => 0);
}


// File upload to S3 and trigger tanscoding to saranyu
function  upload_file_to_S3_and_initiate_transcoding($mform)
{
    global $CFG;

    // Create temp directory 'instaplay'
    $tempdir = 'instaplay';
    make_temp_directory($tempdir);

    require_sesskey();

    $filename = $mform->get_new_filename('userfile');
    $filename =  $filename . time();

    $file = $CFG->tempdir . '/' . $tempdir . '/' . $filename;

    //save file to temp directory
    $status = $mform->save_file('userfile', $file);

    $videoid = '';

    if ($status) {
        $instaplay_videoupload = new instaplay_videoupload();
        $videoid = $instaplay_videoupload->uploadandingest($file, $filename);
    }

    // Delete the temp file, not needed anymore.
      if (!empty($videoid) && file_exists($file)) {
        unlink($file);
    }

    return $videoid;
}


/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $url        url object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function instaplay_view($instaplay, $course, $cm, $context)
{

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $instaplay->id
    );

    $event = \mod_instaplay\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('instaplay', $instaplay);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}
/**
 * Delete instaplay instance.
 * @param int $id
 * @return bool true
 */
function instaplay_delete_instance($id)
{
    global $DB;

    if (!$instaplay = $DB->get_record('instaplay', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('instaplay', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'instaplay', $id, null);

    // Note: all context files are deleted automatically.

    $DB->delete_records('instaplay', array('id' => $instaplay->id));

    return true;
}

/**
 * Update instaplay instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function instaplay_update_instance($data, $mform)
{
    global $CFG, $DB;

    $data->timemodified = time();
    $data->id           = $data->instance;

    $filename = $mform->get_new_filename('userfile');

    if (!empty($filename)) {
        $videoid = upload_file_to_S3_and_initiate_transcoding($mform);

        if (!empty($videoid)) {
            $data->videoid = $videoid;
            $data->playbackurl = null;
            $data->posterurl = null;
            $data->duration= null;
        }
    }

    $DB->update_record('instaplay', $data);
    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'instaplay', $data->id, $completiontimeexpected);

    return true;
}


/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link course_modinfo::get_array_of_activities()}
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function instaplay_get_coursemodule_info($coursemodule)
{
    global $CFG, $DB;

    if (!$instaplay = $DB->get_record('instaplay', array('id' => $coursemodule->instance),
        'id, intro, playbackurl, posterurl, catalogid, contentid, duration, videotitle, introformat'
    )) {

        return null;
    }

    $info = new cached_cm_info();
    $info->name = $instaplay->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('instaplay', $instaplay, $coursemodule->id, false);
    }

    // The icon will be filtered if it will be the default module icon.
 /*    $info->customdata['filtericon'] = empty($info->icon); */

    return $info;
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function instaplay_reset_userdata($data): array
{

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.

    return array();
}
/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function instaplay_get_view_actions()
{
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function instaplay_get_post_actions()
{
    return array('update', 'add');
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function instaplay_dndupload_handle($uploadinfo)
{
    // Gather all the required data.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->intro = '<p>' . $uploadinfo->displayname . '</p>';
    $data->introformat = FORMAT_HTML;
    $data->timemodified = time();
    $data->coursemodule = $uploadinfo->coursemodule;
    $data->videotitle = $uploadinfo->videotitle;
    $data->catalogid = $uploadinfo->catalogid;
    $data->contentid = $uploadinfo->contentid;
    $data->playbackurl = $uploadinfo->playbackurl;
    $data->posterurl = $uploadinfo->posterurl;
    $data->duration = $uploadinfo->duration;
    return instaplay_add_instance($data, null);
}



/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function instaplay_check_updates_since(cm_info $cm, $from, $filter = array())
{
    $updates = course_check_module_updates_since($cm, $from, array('content'), $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid ID override for calendar events
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_instaplay_core_calendar_provide_event_action(
    calendar_event $event,
    \core_calendar\action_factory $factory,
    $userid = 0
) {

    global $USER;
    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['instaplay'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/instaplay/view.php', ['id' => $cm->id]),
        1,
        true
    );
}
