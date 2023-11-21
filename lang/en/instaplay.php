<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     mod_instaplay
 * @category    string
 * @copyright   2023 Shruthi S
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'instaplay';
$string['modulename'] = 'Instaplay';
$string['modulename_help'] = 'The Instaplay module offers a secure video integration into Moodle course.

Instaplay provides functionality to upload videos to amazon S3 and transcoding of the video with Saranyu video transcoding platform.
Provides saranyu embedded video player along with transcribe functionality of the video.';

$string['clicktoopen'] = 'Click on {$a} to open the resource.';
$string['modulename_link'] = 'mod/instaplay/view';
$string['modulenameplural'] = 'instaplay';
$string['privacy:metadata'] = 'The Text and media area plugin does not store any personal data.';
$string['pluginadministration'] = 'Text and media area administration';
$string['search:activity'] = 'instaplay';
$string['serverurl'] = 'Server instaplay';
$string['instaplay:addinstance'] = 'Add a new instaplay resource';
$string['instaplay:view'] = 'View instaplay';
$string['instaplay:addoverlaycomment'] = 'instaplay:addoverlaycomment';
$string['instaplay:viewoverlaycomment'] = 'instaplay:viewoverlaycomment';

$string['name'] = 'Name';
$string['name_help'] = 'Name of the instaplay';
$string['catalogid'] = 'Catalog id';
$string['catalogid_help'] = 'Please enter catalog id of the video';
$string['contentid'] = 'Content id';
$string['contentid_help'] = 'Please enter content id of the video';
$string['duration'] = 'Duration';
$string['duration_help'] = 'Duration of the video';
$string['playbackurl'] = 'Playback URL';
$string['playbackurl_help'] = 'Please enter playback URL of the video';
$string['posterurl'] = 'Poster URL';
$string['posterurl_help'] = 'Please enter poster URL of the video';
$string['invalidurl'] = 'Entered URL is invalid';
$string['invalidstoredurl'] = 'Cannot display this resource, URL is invalid.';
$string['videotitle'] = 'Title';
$string['videotitle_help'] = 'Title on the video';
$string['userfile']='userfile';
$string['userfile_help']='Please upload video in MP3 or MP4 format';
$string['overlaycomments'] ='Overlay comments';
$string['overlay'] = 'overlay';
$string['addnewoverlay'] = 'Add new overlay comment';
$string['editoverlay'] = 'Edit overlay comment';
$string['starttime'] = 'Start time';
$string['starttime_help'] = 'Please enter start time of the overlay comment on the video';
$string['endtime'] = 'End time';
$string['endtime_help'] = 'Please enter end time of the overlay comment on the video';
$string['position'] = 'Position';
$string['position_help'] = 'Please select position of the overlay comment onthe video';
$string['comment'] = 'Comment';
$string['comment_help'] = 'Please enter the comment';
$string['type'] = 'Type';
$string['type_help'] = 'Please select the type of overlay comment on the video';
$string['title'] = 'Title';
$string['title_help'] = 'Please enter title for the options';
$string['infoTosttitle'] = 'Title';
$string['infoTosttitle_help'] = 'Please enter the title';
$string['deletecheck'] = 'Are you sure you want to completely delete the overlay comment?';
$string['commentdeleted'] = 'Comment has been deleted now';
$string['deletecomment'] = 'Delete instaplay comment';
$string['instaplayer'] = 'https://dvskt2g141mcr.cloudfront.net/LMS/index.html?';

$string['selectposition'] = 'Please select position';
$string['topleft'] = 'Left top';
$string['topright'] = 'Right top';
$string['bottomright'] = 'Right bottom';
$string['bottomleft'] = 'Left bottom';
$string['center'] = 'Center';

$string['selecttype'] = 'Please select type';
$string['infotoast'] = 'InfoToast';
$string['radioToast'] = 'RadioToast';
$string['image'] = 'Image';

$string['option'] = 'Option {no}';
$string['addmoreoptions'] = 'Add more options';

$string['save'] = 'Save';
$string['update'] = 'Update';

$string['s3_settings_title'] = 'AWS S3 bucket settings';
$string['s3_access_key'] = 'Access key';
$string['s3_secret_key'] = 'Secret key';
$string['s3_cdn'] = 'CDN';
$string['s3_bucket_name'] = 'Bucket name';
$string['s3_endpoint'] = 'Amazon S3 endpoint';


