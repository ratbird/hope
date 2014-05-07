<?php
namespace RESTAPI\Routes;

/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 *
 * @condition course_id ^[a-f0-9]{32}$
 * @condition file_id ^[a-f0-9]{32}$
 */
class Files extends \RESTAPI\RouteMap
{
    // Bootstrap:
    // - Check if file exists and if it is accessable
    // - Declare helper function "adjustFile"
    public function before($router, &$handler, &$parameters)
    {
        require_once 'lib/datei.inc.php';
    }

    /**********************************************/
    /*                                            */
    /* Files Routes                               */
    /*                                            */
    /**********************************************/


    /**
     * (Meta-)Daten einer Datei bzw. eines Ordners
     *
     * @get /file/:file_id
     */
    public function getFile($file_id) {

        // is it a file?
        if ($file = $this->loadFile($file_id)) {
            return $this->fileToJSON($file);
        }

        // or is it a folder?
        else if ($folder = $this->loadFolder($file_id)) {
            return $this->folderToJSON($folder);
        }

        $this->halt(404, "Not found.");
    }


    /**
     * Inhalte einer Datei
     *
     * @get /file/:file_id/content
     *
     * @see public/sendfile.php
     */
    public function getFileContent($file_id)
    {
        $file = $this->loadFile($file_id);

        if (!isset($file)) {
            $this->notFound("File not found");
        }

        if ($file->url) {
            $this->streamLinkedFile($file);
        }

        else {
            if (!file_exists($real_file = get_upload_file_path($file_id))) {
                $this->notFound();
            }

            TrackAccess($file_id, 'dokument');

            $this->lastModified($file->chdate);
            $this->sendFile($real_file, array('filename' => $file->getValue('filename')));
        }
    }


    /**
     * Create file or folder. To create a file just attach a file as multipart request.
     * If no file is attached, this will create a folder.
     *
     * @post /file/:folder_id
     * @param string $file_id : id of the folder to insert the file or folder to.
     *
     * @param string name : if set the document will have this name instead of the filename. For folders this attribute is mandatory.
     * @param string description : sets the description of the document or folder.
     */
    public function addFile($folder_id)
    {
        $parentFolder = $this->loadFolder($folder_id);
        if (!$parentFolder) {
             $this->error(404, 'folder does not exist');
        }
        if (is_array($this->data['_FILES']) && count($this->data['_FILES'])) {
                //fileupload
                $file = current($this->data['_FILES']);
                $GLOBALS['msg'] = '';
                validate_upload($file);
                if ($GLOBALS['msg']) {
                    $this->error(400, decodeHTML(trim(substr($GLOBALS['msg'],6), '§')));
                }
                if ($file['size']) {
                    $document = array();
                    foreach(words('name description protected') as $c) {
                        if (isset($this->data[$c])) {
                            $document[$c] = $this->data[$c];
                        }
                    }
                    $document['filename'] = strtolower($file['name']);
                    $document['name'] = $document['name'] ?: $document['filename'];
                    $document['user_id'] = $GLOBALS['user']->id;
                    $document['author_name'] = get_fullname();
                    $document['filesize'] = $file['size'];
                    $document['autor_host']  = $_SERVER['REMOTE_ADDR'];
                    $document['range_id'] = $parentFolder->id;
                    $document['seminar_id'] = $parentFolder->seminar_id;
                    $document = \StudipDocument::createWithFile($file['tmp_name'], $document);
                    @unlink($file['tmp_name']);
                }
                if (!$document) {
                    $this->error(400, 'could not create file');
                } else {
                    $this->redirect('file/' . $document->getId(), 201, "ok");
                }
        } elseif($this->data['name']) {
            //create folder
            $newFolder = new \DocumentFolder();
            $newFolder['range_id'] = $parentFolder['folder_id'];
            $newFolder['seminar_id'] = $parentFolder['seminar_id'];
            $newFolder['name'] = $this->data['name'];
            $newFolder['description'] = $this->data['description'];
            $newFolder['user_id'] = $GLOBALS['user']->id;
            $newFolder->store();
            $this->redirect('file/' . $newFolder->getId(), 201, "ok");
        } else {
            $this->error(400, 'name is missing');
        }
    }

    /**
     * Update einer Datei bzw. eines Ordners
     *
     * @put /file/:file_id
     */
    public function putFile($id) {

        $folder = $this->loadFolder($id);
        if (!$folder) {
            $document = $this->loadFile($id);
            $folder = $this->loadFolder($document['range_id']);
        }
        if (!$folder) {
            $this->error(404, 'folder does not exist');
        }
        if ($document) {
            foreach(words('name description protected') as $c) {
                if (isset($this->data[$c])) {
                    $document[$c] = $this->data[$c];
                }
            }
            if (is_array($this->data['_FILES']) && count($this->data['_FILES'])) {
                //fileupload
                $file = current($this->data['_FILES']);
                $GLOBALS['msg'] = '';
                validate_upload($file);
                if ($GLOBALS['msg']) {
                    $this->error(400, decodeHTML(trim(substr($GLOBALS['msg'],6), '§')));
                }
                if ($file['size']) {
                    $document['filename'] = strtolower($file['name']);
                    $document['user_id'] = $GLOBALS['user']->id;
                    $document['author_name'] = get_fullname();
                    $document['filesize'] = $file['size'];
                    $document['autor_host']  = $_SERVER['REMOTE_ADDR'];
                    $ok = \StudipDocument::createWithFile($file['tmp_name'], $document);
                    @unlink($file['tmp_name']);
                }
                if (!$ok) {
                    $this->error(400, 'could not create file');
                }
            } else {
                $document->store();
            }
        } else {
            //update folder
            foreach(words('name description') as $c) {
                if (isset($this->data[$c])) {
                    $folder[$c] = $this->data[$c];
                }
            }
            $folder->store();
        }
        $this->status(204);
        $this->body(null); //no content means no content
    }

    /**
     * Löschen einer Datei bzw. eines Ordners
     *
     * @delete /file/:file_id
     */
    public function deleteFile($file_id) {
        $folder = $this->loadFolder($id);
        if (!$folder) {
            $document = $this->loadFile($id);
            $folder = $this->loadFolder($document['range_id']);
        }
        if (!$folder) {
            $this->error(404);
            return;
        }
        if ($document) {
            $document->delete();
        } else {
            $folder->delete();
        }
    }

    /**
     * Dateien/Ordner einer Veranstaltung
     *
     * @get /course/:course_id/files
     */
    public function getCourseFiles($course_id) {

        $folders = \SimpleCollection::createFromArray(
            \DocumentFolder::findBySeminar_id($course_id))->orderBy('name asc');

        // slice according to demanded pagination
        $total = count($folders);
        $json = array();
        foreach ($folders->limit($this->offset, $this->limit) as $folder) {
            $url = $this->urlf('/file/%s', array($folder->id));
            $json[$url] = $this->folderToJSON($folder, true);
        }
        return $this->paginated($json, $total, compact('course_id'));
    }


    /**
     * Returns the file indicated by the $id if it exists otherwise
     * returns NULL. If the file exists, halt the router with a 403,
     * if the user does not have access.
     */
    private function loadFile($id)
    {
        $file = new \StudipDocument($id);

        // return NULL unless it exists
        if ($file->isNew()) {
            return null;
        }

        if (!$file->checkAccess($GLOBALS['user']->id)) {
            $this->error(401);
        }

        return $file;
    }


    private function loadFolder($id)
    {
        $folder = new \DocumentFolder($id);

        // return NULL unless it exists
        if ($folder->isNew()) {
            return null;
        }

        $seminar_id = $folder->seminar_id;

        if (!self::isDocumentModuleActivated($seminar_id)) {
            $this->error(404, "Not found.");
        }

        if (!$GLOBALS['perm']->have_studip_perm('user', $seminar_id, $GLOBALS['user']->id)) {
            $this->error(401);
        }

        return $folder;
    }


    /**
     * Transforms file metadata to a filtered JSON set of attributes.
     */
    private function fileToJSON($file) {

        $result = array('file_id' => $file->getValue('id'));
        foreach (words('range_id seminar_id name description mkdate chdate filename filesize downloads protected') as $word) {
            $result[$word] = $file->getValue($word);
        }

        // string to int conversions as SORM does not know about ints
        foreach (words("chdate mkdate filesize downloads") as $key) {
            $result[$key] = intval($result[$key]);
        }

        $result['author']    = $this->urlf('/user/%s', array($file->getValue('user_id')));
        $result['protected'] = !empty($result['protected']);
        $result['content']   = $this->urlf('/file/%s/content', array($result['file_id']));

        $this->linkToCourseOrInst($result);

        return $result;
    }


    /**
     * Transforms folder metadata to a filtered JSON set of attributes.
     */
    private function folderToJSON($folder, $shallow = false) {

        $result = array('folder_id' => $folder->getValue('id'));
        foreach (words('range_id seminar_id user_id name description mkdate chdate') as $word) {
            $result[$word] = $folder->getValue($word);
        }

        // string to int conversions as SORM does not know about ints
        foreach (words("chdate mkdate") as $key) {
            $result[$key] = intval($result[$key]);
        }

        // transform user_id
        $result['author'] = $this->urlf('/user/%s', array($result['user_id']));
        unset($result['user_id']);

        $this->linkToCourseOrInst($result);

        $result['permissions'] = $folder->getPermissions();

        $result['documents'] = $this->loadDocuments($folder->id);

        if (!$shallow) {
            $result['folders'] = self::loadFolders($folder->id);
        }

        return $result;
    }

    // transform seminar_id
    private function linkToCourseOrInst(&$result)
    {
        $type = get_object_type($result['seminar_id'], array('sem', 'inst'));

        if ($type === 'sem') {
            $result['course'] = $this->urlf('/course/%s', array($result['seminar_id']));
        } else if ($type === 'inst') {
            $result['institute'] = $this->urlf('/institute/%s', array($result['seminar_id']));
        }
        unset($result['seminar_id']);
    }


    private static function loadFolders($range_id)
    {
        $query = "SELECT folder_id, name, mkdate, chdate, permission,
                         IFNULL(description, '') AS description
                   FROM folder
                   WHERE range_id IN (:range_id, MD5(CONCAT(:range_id, 'top_folder')))
                     AND permission > 0

                   UNION

                   SELECT DISTINCT folder_id, folder.name,
                                   folder.mkdate, folder.chdate, folder.permission,
                                   IFNULL(folder.description, '') AS description
                   FROM themen AS th
                   INNER JOIN folder ON (th.issue_id = folder.range_id)
                   WHERE th.seminar_id = :range_id AND folder.permission > 0

                   UNION

                   SELECT folder_id, folder.name,
                          folder.mkdate, folder.chdate, folder.permission,
                          IFNULL(folder.description, '') AS description
                   FROM statusgruppen sg
                   INNER JOIN statusgruppe_user AS sgu
                     ON (sg.statusgruppe_id = sgu.statusgruppe_id AND sgu.user_id = :user_id)
                   INNER JOIN folder ON (sgu.statusgruppe_id = folder.range_id)
                   WHERE sg.range_id = :range_id AND folder.permission > 0";
        $statement = \DBManager::get()->prepare($query);
        $statement->bindParam(':range_id', $range_id);
        $statement->bindParam(':user_id', $GLOBALS['user']->id);
        $statement->execute();
        $folders =  $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($folders as &$folder) {
           $folder['permissions'] = self::permsToArray($folder['permission']);
           unset($folder['permission']);
        }

        return $folders;
    }

    private static function permsToArray($permission)
    {
        foreach (array(1=>'visible', 'writable', 'readable', 'extendable') as $bit => $perm) {
          if ($permission & $bit)
            $result[] = $perm;
        }
        return $result;
    }

    private function loadDocuments($folder_id)
    {
        $files = \StudipDocument::findByFolderId($folder_id);
        $result = array();
        foreach ($files as $file) {
            $url = $this->urlf('/file/%s', array($file->id));
            $result[$url] = $this->fileToJSON($file);
        }
        return $result;
    }


    private static function isDocumentModuleActivated($range_id)
    {
        // Documents is 2nd bit (0-based indexed!) in modules flag
        $query = "SELECT modules & (1 << 1) != 0 FROM seminare WHERE Seminar_id = ?";
        $statement = \DBManager::get()->prepare($query);
        $statement->execute(array($range_id));
        return $statement->fetchColumn();
    }


    private function streamLinkedFile($file)
    {
        $link_data = parse_link($file->getValue('url'));
        if ($link_data['response_code'] != 200) {
            $this->error(404, sprintf('File contents for file %s not found', $file->id));
        }

        $filename = $file->getValue('filename');
        $headers = array(
            "Content-Type"        => get_mime_type($filename),
            "Content-Disposition" => sprintf('attachment; filename="%s"', basename($filename))
        );

        if ($filesize = $link_data['Content-Length']) {
            $headers["Content-Length"] = $filesize;
        }

        $this->halt(200, $headers, function () use ($file) { readfile($file->url); });
    }
}
