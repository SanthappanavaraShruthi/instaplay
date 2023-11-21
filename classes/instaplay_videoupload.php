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

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Exception;
use moodle_exception;

require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

/**
 * User class for video upload to S3.
 *
 * @todo       move api's from user/lib.php and deprecate old ones.
 * @package    instaplay
 * @copyright  2023 Shruthi S
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class instaplay_videoupload
{

    public function uploadandingest($file, $filename)
    {
        $videoid = "";
        $region = get_config("instaplay", "s3_endpoint");
        $version = 'latest';
        $access_key_id = get_config("instaplay", "s3_access_key");
        $secret_access_key = get_config("instaplay", "s3_secret_key");
        $bucket = get_config("instaplay", "s3_bucket_name");

            $s3 = new S3Client([
                'version' => $version,
                'region' => $region,
                'credentials' => [
                    'key' => $access_key_id,
                    'secret' => $secret_access_key
                ]
            ]);

            // Generate file name
            $filename = $this->instaplay_generate_file_name($filename);

            // Upload the file to amazon s3
            try {
                $result = $s3->putObject([
                    'Bucket' => $bucket,
                    'Key' => $filename,
                    'SourceFile' => $file,
                    'ContentType' => mime_content_type($file)
                ]);

                // handle response
                $result_arr = $result->toArray();
                if (!empty($result_arr['ObjectURL'])) {
                    // S3 url with out CDN
                    $s3_file_link = $result_arr['ObjectURL'];
                    if (!empty($s3_file_link)) {
                        $videoid = $this->instaplay_transcode_s3_video($filename);
                     return $videoid;
                    }
                } else {
                    $api_error = 'Upload failed on S3 Object URL not found';
                }
            } catch (S3Exception $e) {
                $api_error = $e->getMessage();
                throw new \moodle_exception('Error uploading file to amazon S3'.$api_error);
            } catch(Exception $e){
                throw new \moodle_exception('Error uploading file to amazon S3'. $e);

        }

        return  $videoid;
    }

   private function instaplay_generate_file_name($file)
{
    $filename = '';
    if (!empty($file)) {
        $array = explode('.', $file);
        $fileName = $array[0];
        $fileName = preg_replace('/\s+/', '_', $fileName);
        $filename = "lms_" . $fileName . '_' . time();
    }
    return $filename;
}


private function instaplay_transcode_s3_video($file)
{
    $curl = curl_init();

    $requestparameters = [
        "title" => $file,
        "asset_id" => $file,
        "source_url" => get_config("instaplay", "s3_cdn") . "/" . $file,
        "language" => "english",
        "video_object_id" => $file,
        "vertical" => "no",
        "profiles" => "Insta LMS profiles",
        "callback_url" => ""
    ];

    $request = json_encode($requestparameters);

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://54.164.43.155:3003/catalog/5e70a63a555f2498f80000b9/media?auth_token=sPdZsxqmdp8LsSHEJRWx',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $request,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));


    $response = curl_exec($curl);

    if (curl_error($curl)) {
        throw new \moodle_exception('Error form video ingest service');
    }
    curl_close($curl);

    return json_decode($response)->asset_id;
}

private function instaplay_save_assetid($instaplayid, $videoid)
{
    global $DB;

    $instaplay = $DB->get_record('instaplay', array('id' => $instaplayid));

    if (!empty($instaplay)) {

        $instaplay->videoid = $videoid;
        $instaplay->posterurl = '';
        $instaplay->playbackurl = '';
        $DB->update_record('instaplay', $instaplay);
    }
}

}