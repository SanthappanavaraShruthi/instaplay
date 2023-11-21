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

require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');
require_once("$CFG->dirroot/mod/instaplay/lib.php");

use Aws\S3\S3Client;

$region = get_config("instaplay", "s3_endpoint");
$version = 'latest';
$access_key_id = get_config("instaplay", "s3_access_key");
$secret_access_key = get_config("instaplay", "s3_secret_key");
$bucket = get_config("instaplay", "s3_bucket_name");
$cdn = get_config("instaplay", "s3_cdn");

/* if (isset($_POST["submit"])) {

    if (!empty($_FILES["userfile"]["name"])) {
        $file_name = basename($_FILES["userfile"]["name"]);
        $file_type = pathinfo($file_name, PATHINFO_EXTENSION);
        $file_size =$_FILES['userfile']['size'];
        $allowedTypes = array('mp4', 'mp3');
        $file_temp_src = $_FILES["userfile"]["tmp_name"];

        if (in_array($file_type, $allowedTypes)) {


            if (is_uploaded_file($file_temp_src)) {
                // Configure s3client
                $s3 = new S3Client([
                    'version' => $version,
                    'region' => $region,
                    'credentials' => [
                        'key' => $access_key_id,
                        'secret' => $secret_access_key
                    ]
                ]);

                // Generate file name
                $file = instaplay_generate_file_name($file_name);

                // Upload the file to amazon s3
                try {
                    $result = $s3->putObject([
                        'Bucket' => $bucket,
                        'Key' => $file,
                        'SourceFile' => $file_temp_src,
                        'ContentType' => mime_content_type($file_temp_src)
                    ]);

                    // handle response
                    $result_arr = $result->toArray();
                    if (!empty($result_arr['ObjectURL'])) {
                        // S3 url with out CDN
                        $s3_file_link = $result_arr['ObjectURL'];
                        if (!empty($s3_file_link)) {
                            $asset_id = instaplay_transcode_s3_video($file);
                            $instaplayid = $_POST['instaplayid'];
                            instaplay_save_assetid($instaplayid, $asset_id);
                        }
                    } else {
                        $api_error = 'Upload failed on S3 Object URL not found';
                    }
                } catch (AWS\S3\Exception\S3Exception $e) {
                    $api_error = $e->getMessage();
                }
                if (empty($api_error)) {
                    $status = 'success';
                    \core\notification::success("File uploaded successfully");
                } else {
                    $statusMsg = $api_error;
                }
            } else {
                \core\notification::error("File upload filed");
            }
        } else {
            \core\notification::error("Only MP4 and MP3 files are allows to upload");
        }
    } else {
        \core\notification::error("Please select a file to upload");
    }
}

function instaplay_generate_file_name($file)
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



function instaplay_transcode_s3_video($file)
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
        debugging("Error from Saranyu content ingestion service for the file " . $file);
    }
    curl_close($curl);

   return json_decode($response)->asset_id;
}

function instaplay_save_assetid($instaplayid, $videoid){
    global $DB;

    $instaplay = $DB->get_record('instaplay', array('id' => $instaplayid));

    if(!empty($instaplay)){

        $instaplay->videoid = $videoid;
        $instaplay->posterurl='';
        $instaplay->playbackurl='';
        $DB->update_record('instaplay', $instaplay);

    }

}
 */