<?php
/**
 * wysiwyg.php - Provide web services for the WYSIWYG editor.
 * 
 **
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @category    Stud.IP
 * @copyright   (c) 2014 Stud.IP e.V.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @since       File available since Release 3.0
 * @author      Robert Costa <rcosta@uos.de>
 */
require_once 'authenticated_controller.php';

use Studip\WysiwygRequest;
use Studip\WysiwygDocument;

use Studip\MarkupPrivate\MediaProxy; // TODO remove debug code


class WysiwygController extends \AuthenticatedController
{
    const UPLOAD_PERMISSION = 'autor'; // minimum permission level for uploading
    const FOLDER_NAME = 'Wysiwyg Uploads';
    const FOLDER_DESCRIPTION = 'Vom WYSIWYG Editor hochgeladene Dateien.';

    /**
     * Handle the WYSIWYG editor's file uploads.
     *
     * Files must be posted as an HTML array named "files":
     *   <input type="file" name="files[]" multiple />
     *
     * Files will be stored in a folder named "Wysiwyg Uploads". If the
     * folder doesn't exist, it will be created.
     *
     * Results are returned as JSON-encoded array:
     *
     * [{"name": filename, "type": mime-type, "url": download-link},
     *  {"name": filename, "type": mime-type, "error": error-message},
     *  ...]
     *
     * Each array-entry corresponds to a single file, each file that was
     * sent with the post request has exactly one entry.
     *
     * Entries with the property "url" correspond to successful uploads.
     * Entries with the property "error" correspond to failed uploads.
     */
    public function upload_action()
    {
        try {
            WysiwygRequest::verifyWritePermission(self::UPLOAD_PERMISSION);
            $folder_id = WysiwygDocument::createFolder(
                self::FOLDER_NAME, self::FOLDER_DESCRIPTION);
            $response = WysiwygDocument::storeUploadedFilesIn($folder_id);
        } catch (AccessDeniedException $e) {
            $response = $e->getMessage();
        }
        $this->render_json($response); // send HTTP response to client
    }

    /**
     * TODO remove this method
     */
    public function test_action()
    {
        // studip must be at localhost/~rcosta/step00256 for tests to work
        // LOAD_EXTERNAL_MEDIA must be set to 'proxy'
        $studip_root = $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'];

        $studip_document = $studip_root
            . 'sendfile.php?type=0&file_id=abc123&file_name=test.jpg';

        $external_document = 'http://pflanzen-enzyklopaedie.eu'
            . '/wp-content/uploads/2012/11/'
            . 'Sumpfdotterblume-multiplex-120x120.jpg';

        $proxy_document = $studip_root
            . 'dispatch.php/media_proxy?url='
            . 'http%3A%2F%2Fpflanzen-enzyklopaedie.eu'
            . '%2Fwp-content%2Fuploads%2F2012%2F11%2F'
            . 'Sumpfdotterblume-multiplex-120x120.jpg';

        $tests = array(
            'invalid url' => NULL, // fail
            $studip_document                      => $studip_document,
            'http://localhost' . $studip_document => $studip_document,
            'http://127.0.0.1' . $studip_document => $studip_document, // fail
            $proxy_document                      => $proxy_document,
            $external_document                   => $proxy_document,
            'http://localhost' . $proxy_document => $proxy_document,
            'http://127.0.0.1' . $proxy_document => $proxy_document, // fail
        );

        $test_results = '';
        forEach ($tests as $i => $o) {
            try {
                $r = MediaProxy\getMediaUrl($i);
            } catch (MediaProxy\InvalidInternalLinkException $e) {
                $r = 'InvalidInternalLinkException';
            }
            $v = ($r == $o) ? '==' : '!=';
            $test_results .= "Utils::getMediaUrl($i)<br>"
                          .  "                == $r<br>"
                          .  "                $v $o<br>"
                          . '<br>';
        }

        $internal_link_tests = '';
        foreach ($tests as $i => $o) {
            $is = is_internal_url($i) ? 'true' : 'false';
            $internal_link_tests .= "$is = is_internal_url($i)<br>";
        }

        $this->render_text('<pre>'
            .'URLHelper::getLink():             '.URLHelper::getLink().'<br>'
            .'URLHelper::getUrl():              '.URLHelper::getUrl().'<br>'
            .'URLHelper::getScriptUrl():        '.URLHelper::getScriptUrl().'<br>'
            .'ABSOLUTE_URI_STUDIP               '.$GLOBALS['ABSOLUTE_URI_STUDIP'].'<br>'
            .'CANONICAL_RELATIVE_PATH_STUDIP    '.$GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'].'<br>'
            .'LOAD_EXTERNAL_MEDIA               '.\Config::get()->LOAD_EXTERNAL_MEDIA.'<br>'
            .'<br>'
            .$test_results
            .'<br>'
            .$internal_link_tests
            .'</pre>');
    }
}
