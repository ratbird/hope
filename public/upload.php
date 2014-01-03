<?php
/**
 * upload.php - Handle file uploads.
 * 
 * Files must be posted as an HTML array with the name "files":
 *   <input type="file" name="files[]" multiple />
 *
 * A folder identifier can be given in the POST variable "folder_id":
 *   <input type="hidden" name="folder_id" value="IDENTIFIER" />
 *
 * If no folder identifier is given, files will be stored in a folder
 * named "Uploads".
 *
 * Results are returned as a JSON-encoded array, in the following
 * format:
 *
 * [{"name": filename, "type": mime-type, "url": download-link},
 *  {"name": filename, "type": mime-type, "error": error-message},
 *  ...]
 *
 * Each array-entry corresponds to a single file, each file that was
 * sent with the HTTP request has exactly one entry.
 *
 * Entries with the property "url" correspond to successful uploads.
 * Entries with the property "error" correspond to failed uploads.
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
 * @copyright   (c) 2013 Stud.IP e.V.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @since       File available since Release 3.0
 * @author      Robert Costa <rcosta@uos.de>
 */
require_once '../lib/exceptions/AccessDeniedException.php';
require_once '../lib/classes/Request.class.php';  // CSRFProtection.php
require_once '../lib/classes/CSRFProtection.php';
require_once '../lib/utils.php';  // includes bootstraph.php

// verify access permissions
Utils\verifyPostRequest();
CSRFProtection::verifyUnsafeRequest();
Utils\startSession();
Utils\verifyPermission('autor');  // minimum permission level for uploading

// get folder ID
if (isset($_POST['folder_id']) && Utils\folderExists($_POST['folder_id'])) {
    $folder_id = $_POST['folder_id'];
} else {
    $folder_id = Utils\createFolder(_('Uploads'), _('Automatisch hochgeladene Dateien (z.B. vom WYSIWYG Editor).'))
        or exit(_('Erstellen des Upload-Ordners fehlgeschlagen.'));
}

// store uploaded files as StudIP documents
$response = array();  // data for HTTP response
foreach (Utils\getUploadedFiles() as $file) {
    try {
        $newfile = Utils\uploadFile($file, $folder_id);
        $response['files'][] = Array(
            'name' => utf8_encode($newfile['filename']),
            'type' => $file['type'],
            'url' => Utils\getDownloadLink($newfile->getId()));
    } catch (AccessDeniedException $e) {  // creation of Stud.IP doc failed
        $response['files'][] = Array(
            'name' => $file['name'],
            'type' => $file['type'],
            'error' => $e->getMessage());
    }
}

// send HTTP response to client
Utils\sendAsJson($response);
