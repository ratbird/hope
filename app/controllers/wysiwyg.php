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
use Studip\Utils;

class WysiwygController extends AuthenticatedController
{
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
    public function upload_action() {
        // verify access permissions
        Utils::verifyPostRequest();
        CSRFProtection::verifyUnsafeRequest();
        Utils::verifyPermission('autor'); // minimum permission level for uploading

        // get folder ID
        try {
            $folder_id = Utils::createFolder(
                _('Wysiwyg Uploads'),
                _('Vom WYSIWYG Editor hochgeladene Dateien.')
            );
        } catch (AccessDeniedException $e) {
            $this->render_json($e->getMessage());
            return;
        }
    
        // store uploaded files as StudIP documents
        $response = array();  // data for HTTP response
        foreach (Utils::getUploadedFiles() as $file) {
            try {
                $newfile = Utils::uploadFile($file, $folder_id);
                $response['files'][] = Array(
                    'name' => utf8_encode($newfile['filename']),
                    'type' => $file['type'],
                    'url' => Utils::getDownloadLink($newfile->getId()));
            } catch (AccessDeniedException $e) {  // creation of Stud.IP doc failed
                $response['files'][] = Array(
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'error' => $e->getMessage());
            }
        }
        $this->render_json($response); // send HTTP response to client
    }

    public function test_action(){
        // studip must be at localhost/~rcosta/step00256 for tests to work
        // LOAD_EXTERNAL_MEDIA must be set to 'proxy'
        $studip_root = '/~rcosta/step00256';

        $studip_document = $studip_root
            . '/sendfile.php?type=0&file_id=abc123&file_name=test.jpg';

        $external_document = 'http://pflanzen-enzyklopaedie.eu'
            . '/wp-content/uploads/2012/11/'
            . 'Sumpfdotterblume-multiplex-120x120.jpg';

        $proxy_document = $studip_root
            . '/dispatch.php/media_proxy?url='
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
            $r = Utils::getMediaUrl($i);
            $v = ($r == $o) ? '==' : '!=';
            $test_results .= "Utils::getMediaUrl($i)<br>"
                          .  "                == $r<br>"
                          .  "                $v $o<br>"
                          . '<br>';
        }

        $this->render_text('<pre>'
            .'Utils::getUrl():           '.Utils::getUrl().'<br>'
            .'Utils::getBaseName():      '.Utils::getBaseName().'<br>'
            .'Utils::getBaseUrl():       '.Utils::getBaseUrl().'<br>'
            .'URLHelper::getLink():      '.URLHelper::getLink().'<br>'
            .'URLHelper::getUrl():       '.URLHelper::getUrl().'<br>'
            .'URLHelper::getScriptUrl(): '.URLHelper::getScriptUrl().'<br>'
            .'<br>'
            .'LOAD_EXTERNAL_MEDIA='.\Config::GetInstance()->getValue('LOAD_EXTERNAL_MEDIA').'<br>'
            .'<br>'
            .$test_results
            .'</pre>');
    }
}
