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

namespace mod_instaplay;

/**
 * instaplay class
 *
 * @package    instaplay
 * @copyright  2023 Shruthi S
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
/**
 * User class to access user details.
 *
 * @todo       move api's from user/lib.php and deprecate old ones.
 * @package    instaplay
 * @copyright  2023 Shruthi S
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instaplay_overlay {

    /* position options for overlay comment. */
    public static function get_position_list() {

        $choices = array();
        $choices[''] = get_string('selectposition', 'instaplay');
        $choices['0'] = get_string('topleft', 'instaplay');
        $choices['1'] = get_string('topright', 'instaplay');
        $choices['2'] = get_string('bottomright', 'instaplay');
        $choices['3'] = get_string('bottomleft', 'instaplay');
        $choices['4'] = get_string('center', 'instaplay');

        return $choices;
    }

     /* Type options for overlay comment. */
    public static function get_type_list() {

        $choices = array();
        $choices[''] = get_string('selecttype', 'instaplay');
       /*  $choices['0'] = get_string('comment', 'instaplay'); */
        $choices['1'] = get_string('radioToast', 'instaplay');
        $choices['2'] = get_string('infotoast', 'instaplay');

        return $choices;
    }

    /**
     * instaplay_fetch_overly_comments
     *
     * @param  mixed $instaplayid
     * @return array
     */
    public static function instaplay_fetch_overlay_comments($instaplayid):array {
        global $DB;

        $positions = self::get_position_list();

        $types = self::get_type_list();

        $commentsdb = $DB->get_recordset('instaplay_overlay', array('instaplayid' => $instaplayid));

        $comments = [];

         foreach ($commentsdb as $comment) {

            if (isset($positions[$comment->position])) {
                $comment->position = $positions[$comment->position];
            }

            if (isset($positions[$comment->type])) {
                $comment->type = $types[$comment->type];

                if($comment->type === 'RadioToast'){
                    $options = self::get_comments_options($comment->id);
                    $comment->options = $options;
                }
            }

            $comments[] = $comment;

        }

        return $comments;

    }



    /**
     * get_comments_options
     *
     * @param  mixed $instaplayoverlayid
     * @return array
     */
    public static function get_comments_options($instaplayoverlayid):array {
        global $DB;
        $options = array();

        $sql = "SELECT i.optionlvalue from {instaplay_overlay_options} i where i.instaplayoverlayid=$instaplayoverlayid";
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


}
