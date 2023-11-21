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
 * Instaplay module main user interface
 *
 * @package    mod_instaplay
 * @copyright  2023 Shruthi S
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/instaplay/lib.php");
require_once("$CFG->dirroot/mod/instaplay/locallib.php");
require_once($CFG->libdir . '/completionlib.php');
require_once("$CFG->dirroot/mod/instaplay/upload.php");

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID.
$u        = optional_param('u', 0, PARAM_INT);         // URL instance id.
$redirect = optional_param('redirect', 0, PARAM_BOOL);
$forceview = optional_param('forceview', 0, PARAM_BOOL);

$confirmuser  = optional_param('confirmuser', 0, PARAM_INT);
$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   // Md5 confirmation hash.
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 5, PARAM_INT);
$searchvalue  = optional_param('perpage', '', PARAM_INT);



if ($u) {  // Two ways to specify the module.
    $instaplay = $DB->get_record('instaplay', array('id' => $u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('instaplay', $instaplay->id, $instaplay->course, false, MUST_EXIST);
} else {
    $cm = get_coursemodule_from_id('instaplay', $id, 0, false, MUST_EXIST);
    $instaplay = $DB->get_record('instaplay', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/instaplay:view', $context);

/*  Completion and trigger events. */
instaplay_view($instaplay, $course, $cm, $context);

$PAGE->set_url('/mod/instaplay/view.php', array('id' => $cm->id));

if ($delete && confirm_sesskey()) {

    $comment = $DB->get_record('instaplay_overlay', array('id' => $delete));

    $returnurl = new moodle_url('/mod/instaplay/view.php', array('id' => $cm->id));
    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deletecomment', 'instaplay'));

        $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
        $deleteurl = new moodle_url($returnurl, $optionsyes);
        $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');

        echo $OUTPUT->confirm(get_string('deletecheck', 'instaplay'), $deletebutton, $returnurl);

        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        if (instaplay_delete_overlaycomment($comment)) {
            \core\session\manager::gc(); // Remove stale sessions.
            redirect($returnurl);
        } else {
            \core\session\manager::gc(); // Remove stale sessions.
            echo $OUTPUT->header();
            echo $OUTPUT->notification($returnurl, get_string('commentdeleted', 'instaplay'));
        }
    }
}

$PAGE->activityheader->set_description(instaplay_get_intro($instaplay, $cm));

$PAGE->requires->js_call_amd('mod_instaplay/instaplay');

instaplay_print_header($instaplay, $cm, $course);


instaplay_show_video($instaplay, $cm, $course, $context);



/* if (optional_param('searchtext', false, PARAM_BOOL) && confirm_sesskey()) {
    $searchtext = optional_param('searchtextvalue', '', PARAM_RAW_TRIMMED);
    $searchvalue = $searchtext;
    $transcript = instaplay_get_transcribe_serch_data($searchvalue);
}

instaplay_search_transcribe_text($searchvalue, $PAGE->url);

if(empty($transcript)){
$table = null;
}else{
    echo '</br>';
    $hits = $transcript['hits'];

    $table = new html_table();
    $table->head = array();
    $table->colclasses = array();

    $table->head[] = "Start Time";
    $table->head[] = "Search Text";
    $table->head[] = "Play";
    $table->colclasses[] = 'rowdata';
    $table->colclasses[] = 'rowdata';
    $table->colclasses[] = 'rowdata';
    $table->id = "transcript";

    $transcribes = $hits['hits'];

    foreach ($transcribes as $entrys) {
        $entry = $entrys["_source"];

        $buttons = array();
        $lastcolumn = '';
        $buttons[] = $OUTPUT->action_link('#', '', new component_action('click', 'startFromTime', array('startTime' =>  $entry["start_time"])), null, new pix_icon('play', 'play video', 'instaplay'));

        $row = array();
        $row[] = $entry["start_time"];
        $row[] = $entry["transcribe_content"];
        $row[] = implode(' ', $buttons);
        $table->data[] = $row;
    }
    if (!empty($table)) {
        echo html_writer::start_tag('div', array('class' => 'no-overflow'));
        echo html_writer::table($table);
        echo html_writer::end_tag('div');
    }else{
        echo '<p>No matching text in the video<p>';
    }
    echo '</div>';
}

echo "<script type='text/javascript'>
    function startFromTime(e, args){

    const iFrame = document.getElementById('instaplayframeElem');
    const data = args.startTime;
    const captionMsg = {
        type: 'caption-time',
        time: data
    }
    iFrame.contentWindow.postMessage(captionMsg, '*');
    iFrame.scrollIntoView();
}


</script>";
 */

// Overlay comments table starts here.
if (has_capability('mod/instaplay:viewoverlaycomment', $context)) {

    echo '<hr>';
    /* instaplay_uploadvideo_s3($instaplay->id); */

    $comments = get_comments($page, $perpage, $instaplay->id);
    $sitecontext = context_system::instance();
    $returnurl = new moodle_url('/mod/instaplay/view.php', array('id' => $cm->id));
    $baseurl = new moodle_url('/mod/instaplay/view.php', array('id' => $cm->id, 'perpage' => $perpage));
    $commentscount = $DB->count_records('instaplay_overlay', array('instaplayid' => $instaplay->id));

    if (!$comments) {
        $match = array();
       /*  echo '<p>No overlay comments found<p>'; */
        $table = null;
    } else {
        $positions = mod_instaplay\instaplay_overlay::get_position_list();
        $types = mod_instaplay\instaplay_overlay::get_type_list();
        foreach ($comments as $key => $comment) {
            if (isset($positions[$comment->position])) {
                $comments[$key]->position = $positions[$comment->position];
            }

            if (isset($positions[$comment->type])) {
                $comments[$key]->type = $types[$comment->type];
            }
        }

        $table = new html_table();
        $table->head = array();
        $table->colclasses = array();

        $table->head[] = get_string('starttime', 'instaplay');
        $table->head[] = get_string('endtime', 'instaplay');
        $table->head[] = get_string('position', 'instaplay');
        $table->head[] = get_string('type', 'instaplay');
        $table->head[] = get_string('edit');
        $table->colclasses[] = 'centeralign';
        $table->id = "overlydata";

        foreach ($comments as $comment) {
            $buttons = array();
            $lastcolumn = '';
            $deleteurl = new moodle_url('/mod/instaplay/view.php', array('id' => $cm->id, 'delete' => $comment->id, 'sesskey' => sesskey()));
            $buttons[] = html_writer::link($deleteurl, $OUTPUT->pix_icon('t/delete', "Delete"));

            $editurl = new moodle_url(
                '/mod/instaplay/editoverlaycomment.php',
                array('instaplay' => $instaplay->id, 'id' => $comment->id)
            );
            $buttons[] = html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', "Edit"));

            $row = array();
            $row[] = $comment->starttime;
            $row[] = $comment->endtime;
            $row[] = $comment->position;
            $row[] = $comment->type;
            $row[] = implode(' ', $buttons);
            $table->data[] = $row;
        }
    }


    if (!empty($table)) {
   /*  echo $OUTPUT->heading(get_string('overlaycomments', 'instaplay')); */
   echo '<h6> Overlay comments </h6>';
        echo html_writer::start_tag('div', array('class' => 'no-overflow'));
        echo html_writer::table($table);
        echo html_writer::end_tag('div');
        echo $OUTPUT->paging_bar($commentscount, $page, $perpage, $PAGE->url);
    }
    if (has_capability('mod/instaplay:addoverlaycomment', $context)) {
        $url = new moodle_url('/mod/instaplay/editoverlaycomment.php', array('instaplay' => $instaplay->id, 'id' => -1));
       echo '<div id="instaplayOverlay">';
        echo $OUTPUT->single_button($url,  get_string('addnewoverlay', 'instaplay'), 'get');
        echo '</div>';
    }

    echo '</div>';

}

echo $OUTPUT->footer();
die;
