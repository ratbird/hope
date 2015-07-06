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
require_once 'app/models/WysiwygRequest.php';
require_once 'app/models/WysiwygDocument.php';

use Studip\WysiwygRequest;
use Studip\WysiwygDocument;

class WysiwygException extends Exception {};

class WysiwygHttpException extends WysiwygException {};

class WysiwygHttpExceptionBadRequest extends WysiwygHttpException
{
    public function __construct($message = '', $previous = null) {
        parent::__construct($message, 400, $previous);
    }
}

class WysiwygHttpExceptionForbidden extends WysiwygHttpException
{
    public function __construct($message = '', $previous = null) {
        parent::__construct($message, 403, $previous);
    }
}

class WysiwygHttpExceptionNotFound extends WysiwygHttpException
{
    public function __construct($message = '', $previous = null) {
        parent::__construct($message, 404, $previous);
    }
}

class WysiwygHttpExceptionMethodNotAllowed extends WysiwygHttpException
{
    public function __construct($message = '', $previous = null) {
        parent::__construct($message, 405, $previous);
    }
}

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
}

