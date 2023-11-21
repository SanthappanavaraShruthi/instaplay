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
 * Allows you to edit a users profile
 *
 * @copyright 2023 Shruthi S
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod_instaplay
 */
require('../../config.php');
require_once($CFG->dirroot . '/mod/instaplay/editoverlaycomment_form.php');
require_once($CFG->dirroot . '/mod/instaplay/locallib.php');

$id = optional_param('id', null, PARAM_INT);    // Overlay comment id; -1 if creating new comment.
$instaplay = optional_param('instaplay', SITEID, PARAM_INT);   // Instaplay id (defaults to Site).
$returnto = optional_param('returnto', null, PARAM_ALPHA);  // Code determining where to return to after save.

$PAGE->set_url('/mod/instaplay/editoverlaycomment.php', array('instaplay' => $instaplay, 'id' => $id));
$instaplay = $DB->get_record('instaplay', array('id' => $instaplay), '*', MUST_EXIST);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');

if ($id == -1) { // Create new comment.
    $overlaycomment = new stdClass();
    $overlaycomment->id = -1;
    $PAGE->set_primary_active_tab('home');
    $PAGE->set_title('Add overlay comment');

} else {
    // Editing existing comment.
    $PAGE->set_title('Edit overlay comment');
    $overlaycomment = $DB->get_record('instaplay_overlay', array('id' => $id), '*', MUST_EXIST);
    if ($overlaycomment->type === '2') {
        $overlaycomment->infoTosttitle = $overlaycomment->title;
    }

    $overlaycomment->option = get_comments_options($overlaycomment->id);
}

$overlaycommentform = new instaplay_editoverlaycomment_form(new moodle_url($PAGE->url, array('returnto' => $returnto)), array(
    'overlaycomment' => $overlaycomment
));


$cm = get_coursemodule_from_instance('instaplay', $instaplay->id, $instaplay->course, false, MUST_EXIST);
$returnurl = new moodle_url('/mod/instaplay/view.php', array('id' => $cm->id));

if ($overlaycommentform->is_cancelled()) {
    redirect($returnurl);
} else if ($overlaycommentnew = $overlaycommentform->get_data()) {
    $overlaycommentcreated = false;
    if ($overlaycommentnew->id == -1) {
        unset($overlaycommentnew->id);
        $overlaycommentnew->instaplayid = $instaplay->id;
        $overlaycommentnew->id = instaplay_create_overlaycomment($overlaycommentnew, false);
        $overlaycommentcreated = true;
    } else {

        instaplay_update_overlaycomment($overlaycommentnew, false);
    }

    redirect($returnurl);
}

// Page settings.

$PAGE->navbar->add("Instaplay comments", $PAGE->url);

if ($id == -1) {
    $PAGE->set_heading(get_string('addnewoverlay', 'instaplay'));
    $PAGE->navbar->add(get_string('addnewoverlay', 'instaplay'));
    echo $OUTPUT->header();
} else {
    $PAGE->set_heading(get_string('editoverlay', 'instaplay'));
    $PAGE->navbar->add(get_string('editoverlay', 'instaplay'));
    echo $OUTPUT->header();
}


// Finally display THE form.
$overlaycommentform->display();

// And proper footer.
echo $OUTPUT->footer();
