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
 * Resource module admin settings and defaults
 *
 * @package    mod_instaplay
 * @copyright  2023 Shruthi S
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/instaplay/lib.php');

$settings->add(new admin_setting_heading('instaplay/settings_header', get_string("s3_settings_title", "instaplay"), ''));

$settings->add(new admin_setting_configpasswordunmask('instaplay/s3_access_key', get_string("s3_access_key", "instaplay"),
                   get_string("s3_access_key", "instaplay"), "", PARAM_RAW_TRIMMED));
$settings->add(new admin_setting_configpasswordunmask('instaplay/s3_secret_key', get_string("s3_secret_key", "instaplay"),
                   get_string("s3_secret_key", "instaplay"), "", PARAM_RAW_TRIMMED));
$settings->add(new admin_setting_configpasswordunmask('instaplay/s3_cdn', get_string("s3_cdn", "instaplay"),
                   get_string("s3_cdn", "instaplay"), "", PARAM_RAW_TRIMMED));
$settings->add(new admin_setting_requiredtext('instaplay/s3_bucket_name', get_string("s3_bucket_name", "instaplay"),
                   get_string("s3_bucket_name", "instaplay"), "", PARAM_RAW_TRIMMED));

$endpointselect = [];
$all = require($CFG->dirroot . '/local/aws/sdk/Aws/data/endpoints.json.php');
$endpoints = $all['partitions'][0]['regions'];
foreach ($endpoints as $key => $value) {
$endpointselect[$key] = $value['description'];
}

$settings->add(new admin_setting_configselect('instaplay/s3_endpoint', get_string("s3_endpoint", "instaplay"),
                   get_string("s3_endpoint", "instaplay"), "", $endpointselect));


}
