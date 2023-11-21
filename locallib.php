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
 * Private instaplay module utility functions
 *
 * @package    mod_instaplay
 * @copyright  2023 Shruthi S
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use PHP_CodeSniffer\Reports\Json;
use Aws\S3\S3Client;

defined('MOODLE_INTERNAL') || die;


require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/instaplay/lib.php");
require_once("$CFG->dirroot/mod/instaplay/upload.php");
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');



/**
 * Get the parameters that may be appended to instaplay
 * @param object $config instaplay module config options
 * @return array array describing opt groups
 */
function instaplay_get_variable_options($config)
{
    global $CFG;

    $options = array();
    $options[''] = array('' => get_string('chooseavariable', 'instaplay'));

    $options[get_string('course')] = array(
        'courseid'        => 'id',
        'coursefullname'  => get_string('fullnamecourse'),
        'courseshortname' => get_string('shortnamecourse'),
        'courseidnumber'  => get_string('idnumbercourse'),
        'coursesummary'   => get_string('summary'),
        'courseformat'    => get_string('format'),
    );

    $options[get_string('modulename', 'instaplay')] = array(
        'instaplayinstance'     => 'id',
        'instaplaycmid'         => 'cmid',
        'instaplayname'         => get_string('name'),
        'instaplayidnumber'     => get_string('idnumbermod'),
    );

    $options[get_string('miscellaneous')] = array(
        'sitename'        => get_string('fullsitename'),
        'serverurl'       => get_string('serverurl', 'instaplay'),
        'currenttime'     => get_string('time'),
        'lang'            => get_string('language'),
    );
    if (!empty($config->secretphrase)) {
        $options[get_string('miscellaneous')]['encryptedcode'] = get_string('encryptedcode');
    }

    $options[get_string('user')] = array(
        'userid'          => 'id',
        'userusername'    => get_string('username'),
        'useridnumber'    => get_string('idnumber'),
        'userfirstname'   => get_string('firstname'),
        'userlastname'    => get_string('lastname'),
        'userfullname'    => get_string('fullnameuser'),
        'useremail'       => get_string('email'),
        'userphone1'      => get_string('phone1'),
        'userphone2'      => get_string('phone2'),
        'userinstitution' => get_string('institution'),
        'userdepartment'  => get_string('department'),
        'useraddress'     => get_string('address'),
        'usercity'        => get_string('city'),
        'usertimezone'    => get_string('timezone'),
    );

    if ($config->rolesinparams) {
        $roles = role_fix_names(get_all_roles());
        $roleoptions = array();
        foreach ($roles as $role) {
            $roleoptions['course' . $role->shortname] = get_string('yourwordforx', '', $role->localname);
        }
        $options[get_string('roles')] = $roleoptions;
    }

    return $options;
}


/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */
function instaplay_filter_callback($matches)
{
    return rawurlencode($matches[0]);
}

/**
 * Print url header.
 * @param object $url
 * @param object $cm
 * @param object $course
 * @return void
 */
function instaplay_print_header($instaplay, $cm, $course)
{
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname . ': ' . $instaplay->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($instaplay);
    echo $OUTPUT->header();
}

/**
 * Get url introduction.
 *
 * @param object $url
 * @param object $cm
 * @param bool $ignoresettings print even if not specified in modedit
 * @return string
 */
function instaplay_get_intro(object $instaplay, object $cm, bool $ignoresettings = false): string
{

    if (trim(strip_tags($instaplay->intro))) {
        return format_module_intro('instaplay', $instaplay, $cm->id);
    }

    return '';
}



function instaplay_get_playbackurl($instaplay)
{
    global $PAGE, $DB;

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://54.164.43.155:3003/media/details/" . $instaplay->videoid . "?auth_token=sPdZsxqmdp8LsSHEJRWx",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        $error_message = curl_error($curl);
        debugging("Error calling service Saranyu transcode video " . $error_message, DEBUG_DEVELOPER);
    }
    curl_close($curl);


    return  json_decode($response);
}


/**
 * instaplay_display_iframe
 *
 * @param  mixed $instaplay
 * @param  mixed $cm
 * @param  mixed $course
 * @param  mixed $context
 * @return void
 */
function instaplay_show_video($instaplay, $cm, $course, $context)
{
    $playerdetails = new stdClass();
    // Fetch overaly comments.
    $comments = \mod_instaplay\instaplay_overlay::instaplay_fetch_overlay_comments($instaplay->id);
    $instaplayoverlaycomments = instaple_overlay_formatcomments($comments);
    $playerdetails->instaplayoverlaycomments = $instaplayoverlaycomments;

    // playback url details
    if (!empty($instaplay->videoid) && empty($instaplay->playbackurl)) {
        $transcoddetails = instaplay_get_playbackurl($instaplay);
        if (!empty($transcoddetails) && !empty($transcoddetails->message->hls_url)) {
            $mediadetails = $transcoddetails->message;
            $instaplay = instapla_update_mediadetails($instaplay,  $mediadetails);
        }
    }
    instaplay_show($playerdetails, $instaplay);
}


function instaplay_show($playerdetails, $instaplay)
{
    global $OUTPUT, $PAGE;

    if (empty($instaplay->videoid)  && empty($instaplay->playbackurl)) {
        echo '<h6>Please upload video or provide Playback URL along with other required details in settings page </h6>';
    } else if (!empty($instaplay->videoid) && empty($instaplay->playbackurl)) {
        $data = new stdClass;
        echo $OUTPUT->render_from_template('mod_instaplay/progressbar', $data);
    } else {
        $player = get_string("instaplayer", "instaplay");

        $overlaycomments = $playerdetails->instaplayoverlaycomments;

        echo '<div class="viewinstaplay">';
        $title = rawurlencode($instaplay->videotitle);

        $src = $player . "src=" . $instaplay->playbackurl .
            "&poster=" . $instaplay->posterurl .
            "&title=" . $title .
            "&catalogid=" . $instaplay->catalogid .
            "&contentid=" . $instaplay->catalogid .
            "&type=dash";

         $PAGE->requires->js_call_amd('mod_instaplay/overlaycomments', 'sendMesage', [$overlaycomments]);

        echo '<iframe id="instaplayframeElem" width="620px" height="349px" src=' . $src . ' allowfullscreen ></iframe>';

    }
}

function  instapla_update_mediadetails($instaplay, $mediadetails)
{
    global $DB;
    if (!empty($mediadetails)) {
        $instaplay->playbackurl = $mediadetails->hls_url;
        $instaplay->posterurl = $mediadetails->thumbnails[0]->url;
        $instaplay->duration = $mediadetails->duration;

        $DB->update_record('instaplay', $instaplay);
    }
    return $instaplay;
}


function instaplay_search_transcribe_text($searchvalue, \moodle_url $pageurl)
{
    global $OUTPUT;

    /*  $data = new stdClass;
    $data->searchvalue= $searchvalue;
    $data->pageurl=$pageurl;

    echo $OUTPUT->render_from_template("mod_instaplay/searchinvideo", $data); */

    echo  html_writer::start_div('search');
    echo   html_writer::start_tag('form', [
        'method' => 'post', 'action' => 'view.php',
        'class' => 'wordsearchform form-inline'
    ]);
    echo  html_writer::start_tag('fieldset', ['class' => 'invisiblefieldset']);
    echo   html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo  html_writer::input_hidden_params($pageurl);
    echo   html_writer::tag('label', "Search in video" . ' ', ['for' => 'inputsearchtext', 'style' => 'display: inline;
    padding-right: 20px;',  'placeholder' => 'Enter text ...']);
    echo   html_writer::empty_tag('input', [
        'type' => 'text', 'id' => 'inputsearchtext',
        'name' => 'searchtextvalue',
        'value' => $searchvalue,
        'class' => 'form-control'
    ]);
    echo   html_writer::empty_tag('input', [
        'type' => 'submit', 'class' => 'btn btn-primary ml-1',
        'name' => 'searchtext', 'value' => 'Search'
    ]);
    echo html_writer::end_tag('fieldset');
    echo  html_writer::end_tag('form');
    echo  html_writer::end_tag('div');
}


function instaplay_get_transcribe_serch_data($searchvalue): array
{
    $curl = curl_init();
    $request = [
        "url" => "http://139.5.189.17:9200/transcribe_demo2/_search",

        "data" => [

            "size" => 100,

            "track_total_hits" => true,

            "query" => [

                "bool" => [

                    "should" => [

                        [

                            "multi_match" => [

                                "query" => $searchvalue,


                                "fields" => [

                                    "celebrity_name",

                                    "celebrity_name.soundex",

                                    "celebrity_name.metaphone",

                                    "celebrity_name.nysiis",

                                    "celebrity_name.rebuilt_bengali",

                                    "celebrity_name.rebuilt_hindi",

                                    "transcribe_content",

                                    "transcribe_content.soundex",

                                    "transcribe_content.metaphone",

                                    "transcribe_content.nysiis",

                                    "transcribe_content.rebuilt_bengali",

                                    "transcribe_content.rebuilt_hindi",

                                    "transliteration",

                                    "transliteration.soundex",

                                    "transliteration.metaphone",

                                    "transliteration.nysiis",

                                    "transliteration.rebuilt_bengali",

                                    "transliteration.rebuilt_hindi"

                                ]

                            ]

                        ]

                    ]

                ]

            ]

        ]
    ];


    $requeststring = json_encode($request);


    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://139.5.189.17:3001/search',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $requeststring,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    if ($response === false) {
        debugging("call to service failed", DEBUG_DEVELOPER);
    } else {
        $result = json_decode($response, true);
    }

    return $result['res'];
}

/**
 * instaple_overlay_formatcomments
 *
 * @param  mixed $comments
 * @return string Json format of the comments
 */
function instaple_overlay_formatcomments($comments): string
{

    $instaplaycomments = array();

    foreach ($comments as $comment) {
        $overlaycomment = new stdClass();

        $overlaycomment->startTime = $comment->starttime;
        $overlaycomment->endTime = $comment->endtime;

        if ($comment->position === 'Left top') {
            $overlaycomment->position = 'leftTop';
        } else if ($comment->position === 'Right top') {
            $overlaycomment->position = 'rightTop';
        } else if ($comment->position === 'Right bottom') {
            $overlaycomment->position = 'rightBottom';
        } else if ($comment->position === 'Left bottom') {
            $overlaycomment->position = 'leftBottom';
        } else if ($comment->position === 'Center') {
            $overlaycomment->position = 'center';
        }


        if ($comment->type === 'Comment') {
            $overlaycomment->type = 'comment';
            $overlaycomment->commentText = $comment->comment;
        } else if ($comment->type === 'InfoToast') {
            $overlaycomment->type = 'infoToast';
            $overlaycomment->title = $comment->title;
        } else if ($comment->type === 'RadioToast') {
            $overlaycomment->type = 'radioToast';
            $overlaycomment->title = $comment->title;
            $overlaycomment->options = $comment->options;
        }

        $instaplaycomments[] = $overlaycomment;
    }

    return json_encode($instaplaycomments);
}



/**
 * get_comments
 *
 * @param  mixed $page
 * @param  mixed $perpage
 * @param  mixed $instaplayid
 * @return array
 */
function get_comments($page, $perpage, $instaplayid): array
{
    global $DB;

    if ($page == 0) {
        $start = 0;
    } else {
        $start = $page * $perpage;
    }
    $comments = array();

    $sql = "SELECT * from {instaplay_overlay} i where i.instaplayid=$instaplayid ORDER BY i.creationtime ASC";
    $rs = $DB->get_recordset_sql($sql, null, $start, $perpage);

    foreach ($rs as $item) {
        $comments[] = $item;
    }
    $rs->close();

    return $comments;
}



/**
 * get_comments_options
 *
 * @param  mixed $instaplayoverlyid
 * @return array
 */
function get_comments_options($instaplayoverlayid): array
{
    global $DB;
    $options = array();

    $sql = "SELECT * from {instaplay_overlay_options} i where i.instaplayoverlayid=$instaplayoverlayid";
    $rs = $DB->get_recordset_sql($sql, null);

    foreach ($rs as $item) {
        $options[] = $item;
    }

    $optionvalues = array();

    foreach ($options as $key => $value) {
        $optionvalues[] = $options[$key]->optionlvalue;
    }

    $rs->close();
    return $optionvalues;
}

/**
 * Get the parameter values that may be appended to instaplay
 * @param object $instaplay module instance
 * @param object $cm
 * @param object $course
 * @param object $config module config options
 * @return array of parameter values
 */
function instaplay_get_variable_values($instaplay, $cm, $course, $config)
{
    global $USER, $CFG;

    $site = get_site();

    $coursecontext = context_course::instance($course->id);

    $values = array(
        'courseid'        => $course->id,
        'coursefullname'  => format_string($course->fullname, true, array('context' => $coursecontext)),
        'courseshortname' => format_string($course->shortname, true, array('context' => $coursecontext)),
        'courseidnumber'  => $course->idnumber,
        'coursesummary'   => $course->summary,
        'courseformat'    => $course->format,
        'lang'            => current_language(),
        'sitename'        => format_string($site->fullname, true, array('context' => $coursecontext)),
        'serverurl'       => $CFG->wwwroot,
        'currenttime'     => time(),
        'urlinstance'     => $instaplay->id,
        'urlcmid'         => $cm->id,
        'urlname'         => format_string($instaplay->name, true, array('context' => $coursecontext)),
        'urlidnumber'     => $cm->idnumber,
    );

    if (isloggedin()) {
        $values['userid']          = $USER->id;
        $values['userusername']    = $USER->username;
        $values['useridnumber']    = $USER->idnumber;
        $values['userfirstname']   = $USER->firstname;
        $values['userlastname']    = $USER->lastname;
        $values['userfullname']    = fullname($USER);
        $values['useremail']       = $USER->email;
        $values['userphone1']      = $USER->phone1;
        $values['userphone2']      = $USER->phone2;
        $values['userinstitution'] = $USER->institution;
        $values['userdepartment']  = $USER->department;
        $values['useraddress']     = $USER->address;
        $values['usercity']        = $USER->city;
        $now = new DateTime('now', core_date::get_user_timezone_object());
        $values['usertimezone']    = $now->getOffset() / 3600.0; // Value in hours for BC.
    }

    // Weak imitation of Single-Sign-On, for backwards compatibility only.
    // NOTE: login hack is not included in 2.0 any more, new contrib auth plugin
    // needs to be createed if somebody needs the old functionality!
    if (!empty($config->secretphrase)) {
        $values['encryptedcode'] = instaplay_get_encrypted_parameter($instaplay, $config);
    }

    if ($config->rolesinparams) {
        $coursecontext = context_course::instance($course->id);
        $roles = role_fix_names(get_all_roles($coursecontext), $coursecontext, ROLENAME_ALIAS);
        foreach ($roles as $role) {
            $values['course' . $role->shortname] = $role->localname;
        }
    }

    return $values;
}


/**
 * BC internal function
 * @param object $instaplay
 * @param object $config
 * @return string
 */
function instaplay_get_encrypted_parameter($instaplay, $config)
{
    global $CFG;

    if (file_exists("$CFG->dirroot/local/externserverfile.php")) {
        require_once("$CFG->dirroot/local/externserverfile.php");
        if (function_exists('extern_server_file')) {
            return extern_server_file($url, $config);
        }
    }
    return md5(getremoteaddr() . $config->secretphrase);
}


/**
 * Optimised mimetype detection from general URL
 * @param $fullurl
 * @param int $size of the icon.
 * @return string|null mimetype or null when the filetype is not relevant.
 */
function instaplay_guess_icon($fullurl, $size = null)
{
    global $CFG;
    require_once("$CFG->libdir/filelib.php");

    if (substr_count($fullurl, '/') < 3 || substr($fullurl, -1) === '/') {
        // Most probably default directory - index.php, index.html, etc. Return null because
        // we want to use the default module icon instead of the HTML file icon.
        return null;
    }

    try {
        // There can be some cases where the url is invalid making parse_url() to return false.
        // That will make moodle_url class to throw an exception, so we need to catch the exception to prevent errors.
        $moodleurl = new moodle_url($fullurl);
        $fullurl = $moodleurl->out_omit_querystring();
    } catch (\moodle_exception $e) {
        // If an exception is thrown, means the url is invalid. No need to log exception.
        return null;
    }

    $icon = file_extension_icon($fullurl, $size);
    $htmlicon = file_extension_icon('.htm', $size);
    $unknownicon = file_extension_icon('', $size);
    $phpicon = file_extension_icon('.php', $size); // Exception for php files.

    // We do not want to return those icon types, the module icon is more appropriate.
    if ($icon === $unknownicon || $icon === $htmlicon || $icon === $phpicon) {
        return null;
    }

    return $icon;
}

/**
 * instaplay_create_overlaycomment
 *
 * @param  mixed $overlycomment
 * @param  mixed $triggerevent
 * @return void
 */
function instaplay_create_overlaycomment($overlaycomment, $triggerevent = true)
{
    global $DB;

    // Set the timecreate field to the current time.
    if (!is_object($overlaycomment)) {
        $overlaycomment = (object) $overlaycomment;
    }

    if ($overlaycomment->type === '2') {
        $overlaycomment->title = $overlaycomment->infoTosttitle;
    }

    $overlaycomment->creationtime = time();
    // Insert the comment into the database.
    $overlaycommentid = $DB->insert_record('instaplay_overlay', $overlaycomment);

    if ($overlaycomment->type === '1') {

        $options = $overlaycomment->option;
        foreach ($options as $key => $value) {
            $value = trim($value);
            $option = new stdClass();
            $option->optionlvalue = $value;
            $option->instaplayoverlayid = $overlaycommentid;
            $option->timemodified = time();
            $DB->insert_record("instaplay_overlay_options", $option);
        }
    }

    return $overlaycommentid;
}


/**
 * instaplay_update_overlaycomment
 *
 * @param  mixed $overlaycomment
 * @return void
 */
function instaplay_update_overlaycomment($overlaycomment)
{
    global $DB;

    // Set the timecreate field to the current time.
    if (!is_object($overlaycomment)) {
        $overlaycomment = (object) $overlaycomment;
    }

    $overlaycomment->timeonvideo = time();

    if ($overlaycomment->type === '1') {
        $options = $overlaycomment->option;
        $DB->delete_records('instaplay_overlay_options', ['instaplayoverlayid' => $overlaycomment->id]);
        foreach ($options as $key => $value) {
            $value = trim($value);
            if (!empty($value)) {
                $option = new stdClass();
                $option->optionlvalue = $value;
                $option->instaplayoverlayid = $overlaycomment->id;
                $option->timemodified = time();
                $DB->insert_record("instaplay_overlay_options", $option);
            }
        }
    } else if ($overlaycomment->type === '2') {
        $overlaycomment->title = $overlaycomment->infoTosttitle;
    }
    $DB->update_record('instaplay_overlay', $overlaycomment);
}

/**
 * instaplay_delete_overlycomment
 *
 * @param  mixed $overlycomment
 * @return void
 */
function instaplay_delete_overlaycomment(stdClass $overlaycomment)
{
    global $DB;

    // Better not trust the parameter and fetch the latest info this will be very expensive anyway.
    $overlaycomment = $DB->get_record('instaplay_overlay', array('id' => $overlaycomment->id));

    if (empty($overlaycomment)) {
        return false;
    } else {
        if ($overlaycomment->type === '1') {
            $DB->delete_records('instaplay_overlay_options', ['instaplayoverlayid' => $overlaycomment->id]);
        }

        $DB->delete_records('instaplay_overlay', ['id' => $overlaycomment->id]);
        return true;
    }
}

function instaplay_uploadvideo_s3($instaplayid)
{

    echo   html_writer::start_tag('form', [
        'method' => 'post', 'action' => "",
        'class' => 'uploadvideo form-inline',
        'enctype' => 'multipart/form-data',
        'id' => 'upload-form'
    ]);

    echo  html_writer::start_tag('div', ['class' => 'form-group']);
    echo   html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'instaplayid', 'value' => $instaplayid]);
    echo   html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo   html_writer::tag('label', "Upload video" . ' ', ['for' => 'uploadfile',  'style' => 'display: inline;
    padding-right: 20px;']);
    echo   html_writer::empty_tag('input', [
        'type' => 'file', 'id' => 'uploadfile',
        'name' => 'userfile',
        'class' => 'form-control',
        'required'
    ]);
    echo  html_writer::end_tag('div');

    echo  html_writer::start_tag('div', ['class' => 'form-group']);

    echo   html_writer::empty_tag('input', [
        'type' => 'submit', 'class' => 'btn btn-primary ml-1',
        'name' => 'submit', 'value' => 'Upload'
    ]);
    echo  html_writer::end_tag('div');

    echo  html_writer::end_tag('form');

    if (!empty($s3_file_link)) {
        echo '<div class="result">';
        echo '<p><b>S3 Object URL:</b><a href="' . $s3_file_link . '" target="_blank">' . $s3_file_link . '</a></p>';
        echo '</div>';
    }
    echo  html_writer::end_tag('div');
}
