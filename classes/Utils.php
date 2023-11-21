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


namespace mod_instaplay;

use CurlHandle;

final class Utils {
    const DEFAULT_JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;


    private static $retriableerrorcodes = [
        CURLE_COULDNT_RESOLVE_HOST,
        CURLE_COULDNT_CONNECT,
        CURLE_HTTP_NOT_FOUND,
        CURLE_READ_ERROR,
        CURLE_OPERATION_TIMEOUTED,
        CURLE_HTTP_POST_ERROR,
        CURLE_SSL_CONNECT_ERROR,
    ];

    /**
     * Executes a CURL request with optional retries and exception on failure
     *
     * @param  resource|CurlHandle $ch             curl handler
     * @param  int                 $retries
     * @param  bool                $closeAfterDone
     * @return bool|string         @see curl_exec
     */
    public static function execute($ch, int $retries = 2, bool $closeafterdone = true) {
        while ($retries--) {
            $curlresponse = curl_exec($ch);
            if ($curlresponse === false) {
                $curlerrno = curl_errno($ch);

                if (false === in_array($curlerrno, self::$retriableerrorcodes, true) || !$retries) {
                    $curlerror = curl_error($ch);

                    if ($closeafterdone) {
                        curl_close($ch);
                    }

                    throw new \RuntimeException(sprintf('Curl error (code %d): %s', $curlerrno, $curlerror));
                }

                continue;
            }

            if ($closeafterdone) {
                curl_close($ch);
            }

            return $curlresponse;
        }

        return false;
    }

    /*
     * Return the JSON representation of a value
     *
     * @param  mixed             $data
     * @param  int               $encodeFlags  flags to pass to json encode, defaults to DEFAULT_JSON_FLAGS
     * @param  bool              $ignoreErrors whether to ignore encoding errors or to throw on error, when
     *                            ignored and the encoding fails, "null" is returned which is valid json for null
     * @throws \RuntimeException if encoding fails and errors are not ignored
     * @return string            when errors are ignored and the encoding fails, "null" is returned which is valid json for null
     */
    public static function jsonencode($data, ?int $encodeflags = null, bool $ignoreerrors = false): string {
        if (null === $encodeflags) {
            $encodeflags = self::DEFAULT_JSON_FLAGS;
        }

        if ($ignoreerrors) {
            $json = @json_encode($data, $encodeflags);
            if (false === $json) {
                return 'null';
            }

            return $json;
        }

        $json = json_encode($data, $encodeflags);
        if (false === $json) {
            $json = self::handleJsonError(json_last_error(), $data);
        }

        return $json;
    }


     /**
      * Handle a json_encode failure.
      *
      * If the failure is due to invalid string encoding, try to clean the
      * input and encode again. If the second encoding attempt fails, the
      * initial error is not encoding related or the input can't be cleaned then
      * raise a descriptive exception.
      *
      * @param  int               $code        return code of json_last_error function
      * @param  mixed             $data        data that was meant to be encoded
      * @param  int               $encodeFlags flags to pass to json encode, defaults to
      *                           JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
      * @throws \RuntimeException if failure can't be corrected
      * @return string            JSON encoded data after error correction
      */
    public static function handlejsonerror(int $code, $data, ?int $encodeflags = null): string {
        if ($code !== JSON_ERROR_UTF8) {
            self::throwencodeerror($code, $data);
        }

        if (is_string($data)) {
            self::detectAndCleanUtf8($data);
        } else if (is_array($data)) {
            array_walk_recursive($data, array('instaplayauth\Utils', 'detectAndCleanUtf8'));
        } else {
            self::throwencodeerror($code, $data);
        }

        if (null === $encodeflags) {
            $encodeflags = self::DEFAULT_JSON_FLAGS;
        }

        $json = json_encode($data, $encodeflags);

        if ($json === false) {
            self::throwencodeerror(json_last_error(), $data);
        }

        return $json;
    }

    /**
     * Throws an exception according to a given code with a customized message
     *
     * @param  int               $code return code of json_last_error function
     * @param  mixed             $data data that was meant to be encoded
     * @throws \RuntimeException
     */
    private static function throwencodeerror(int $code, $data): void {
        switch ($code) {
            case JSON_ERROR_DEPTH:
                $msg = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $msg = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $msg = 'Unexpected control character found';
                break;
            case JSON_ERROR_UTF8:
                $msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $msg = 'Unknown error';
        }

        throw new \RuntimeException('JSON encoding failed: '.$msg.'. Encoding: '.var_export($data, true));
    }


        /**
         * Detect invalid UTF-8 string characters and convert to valid UTF-8.
         *
         * Valid UTF-8 input will be left unmodified, but strings containing
         * invalid UTF-8 codepoints will be reencoded as UTF-8 with an assumed
         * original encoding of ISO-8859-15. This conversion may result in
         * incorrect output if the actual encoding was not ISO-8859-15, but it
         * will be clean UTF-8 output and will not rely on expensive and fragile
         * detection algorithms.
         *
         * Function converts the input in place in the passed variable so that it
         * can be used as a callback for array_walk_recursive.
         *
         * @param mixed $data Input to check and convert if needed, passed by ref
         */
    private static function detectandcleanutf8(&$data): void {
        if (is_string($data) && !preg_match('//u', $data)) {
            $data = preg_replace_callback(
                '/[\x80-\xFF]+/',
                function ($m) {
                    return utf8_encode($m[0]);
                },
                $data
            );
            $data = str_replace(
                ['¤', '¦', '¨', '´', '¸', '¼', '½', '¾'],
                ['€', 'Š', 'š', 'Ž', 'ž', 'Œ', 'œ', 'Ÿ'],
                $data
            );
        }
    }

}

